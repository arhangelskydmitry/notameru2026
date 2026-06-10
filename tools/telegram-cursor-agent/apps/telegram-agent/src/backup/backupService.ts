import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const execFileAsync = promisify(execFile);

interface SnapshotEntry {
  path: string;
  existed: boolean;
  sha256?: string;
}

export interface SnapshotMetadata {
  id: string;
  taskId: string;
  createdAt: string;
  workingDirectory: string;
  files: SnapshotEntry[];
  changedFiles?: string[];
  diffSummary?: string;
}

export interface RollbackResult {
  snapshotId: string;
  restoredFiles: string[];
  removedFiles: string[];
}

function safeTimestamp(): string {
  return new Date().toISOString().replace(/[:.]/g, "-");
}

function toRelativeProjectPath(workingDirectory: string, filePath: string): string | null {
  const relative = path.relative(workingDirectory, path.resolve(workingDirectory, filePath));

  if (!relative || relative.startsWith("..") || path.isAbsolute(relative)) {
    return null;
  }

  return relative.split(path.sep).join("/");
}

function sha256(filePath: string): string {
  const hash = crypto.createHash("sha256");
  hash.update(fs.readFileSync(filePath));
  return hash.digest("hex");
}

async function gitOutput(workingDirectory: string, args: string[]): Promise<string> {
  const { stdout } = await execFileAsync("git", args, {
    cwd: workingDirectory,
    encoding: "buffer",
    maxBuffer: 64 * 1024 * 1024
  });

  return stdout.toString("utf8");
}

async function listGitVisibleFiles(workingDirectory: string): Promise<string[]> {
  const output = await gitOutput(workingDirectory, [
    "ls-files",
    "--cached",
    "--others",
    "--exclude-standard",
    "-z"
  ]);

  return output
    .split("\0")
    .map((item) => item.trim())
    .filter(Boolean)
    .sort();
}

async function gitDiffSummary(workingDirectory: string): Promise<string> {
  try {
    const [status, stat] = await Promise.all([
      gitOutput(workingDirectory, ["status", "--short"]),
      gitOutput(workingDirectory, ["diff", "--stat"])
    ]);

    return [status.trim(), stat.trim()].filter(Boolean).join("\n\n");
  } catch {
    return "";
  }
}

function metadataPath(snapshotDirectory: string): string {
  return path.join(snapshotDirectory, "metadata.json");
}

export class BackupService {
  constructor(
    private readonly workingDirectory: string,
    private readonly backupDirectory: string
  ) {}

  async createSnapshot(taskId: string): Promise<SnapshotMetadata> {
    const id = `${safeTimestamp()}-${taskId.slice(0, 8)}`;
    const snapshotDirectory = path.join(this.backupDirectory, id);
    const filesDirectory = path.join(snapshotDirectory, "files");
    const files = await listGitVisibleFiles(this.workingDirectory);
    const entries: SnapshotEntry[] = [];

    fs.mkdirSync(filesDirectory, { recursive: true, mode: 0o700 });

    for (const relativePath of files) {
      const normalizedPath = toRelativeProjectPath(this.workingDirectory, relativePath);
      if (!normalizedPath) {
        continue;
      }

      const sourcePath = path.join(this.workingDirectory, normalizedPath);
      const backupPath = path.join(filesDirectory, normalizedPath);

      if (!fs.existsSync(sourcePath) || !fs.statSync(sourcePath).isFile()) {
        entries.push({ path: normalizedPath, existed: false });
        continue;
      }

      fs.mkdirSync(path.dirname(backupPath), { recursive: true, mode: 0o700 });
      fs.copyFileSync(sourcePath, backupPath);
      entries.push({
        path: normalizedPath,
        existed: true,
        sha256: sha256(sourcePath)
      });
    }

    const metadata: SnapshotMetadata = {
      id,
      taskId,
      createdAt: new Date().toISOString(),
      workingDirectory: this.workingDirectory,
      files: entries
    };

    this.writeMetadata(metadata);
    return metadata;
  }

  async finalizeSnapshot(snapshotId: string): Promise<SnapshotMetadata> {
    const metadata = this.readSnapshot(snapshotId);
    const before = new Map(metadata.files.map((entry) => [entry.path, entry]));
    const afterFiles = await listGitVisibleFiles(this.workingDirectory);
    const allPaths = new Set([...before.keys(), ...afterFiles]);
    const changedFiles: string[] = [];

    for (const relativePath of [...allPaths].sort()) {
      const normalizedPath = toRelativeProjectPath(this.workingDirectory, relativePath);
      if (!normalizedPath) {
        continue;
      }

      const previous = before.get(normalizedPath);
      const currentPath = path.join(this.workingDirectory, normalizedPath);
      const existsNow = fs.existsSync(currentPath) && fs.statSync(currentPath).isFile();

      if (!previous) {
        if (existsNow) {
          changedFiles.push(normalizedPath);
        }
        continue;
      }

      if (previous.existed !== existsNow) {
        changedFiles.push(normalizedPath);
        continue;
      }

      if (previous.existed && existsNow && previous.sha256 !== sha256(currentPath)) {
        changedFiles.push(normalizedPath);
      }
    }

    const updated: SnapshotMetadata = {
      ...metadata,
      changedFiles,
      diffSummary: await gitDiffSummary(this.workingDirectory)
    };

    this.writeMetadata(updated);
    return updated;
  }

  readSnapshot(snapshotId: string): SnapshotMetadata {
    return JSON.parse(
      fs.readFileSync(metadataPath(path.join(this.backupDirectory, snapshotId)), "utf8")
    ) as SnapshotMetadata;
  }

  latestSnapshot(): SnapshotMetadata | null {
    if (!fs.existsSync(this.backupDirectory)) {
      return null;
    }

    const snapshots = fs
      .readdirSync(this.backupDirectory, { withFileTypes: true })
      .filter((entry) => entry.isDirectory())
      .map((entry) => {
        try {
          return this.readSnapshot(entry.name);
        } catch {
          return null;
        }
      })
      .filter((entry): entry is SnapshotMetadata => entry !== null)
      .sort((left, right) => right.createdAt.localeCompare(left.createdAt));

    return snapshots[0] ?? null;
  }

  rollback(snapshotId: string): RollbackResult {
    const metadata = this.readSnapshot(snapshotId);
    const changedFiles = metadata.changedFiles ?? [];
    const restoredFiles: string[] = [];
    const removedFiles: string[] = [];
    const before = new Map(metadata.files.map((entry) => [entry.path, entry]));

    for (const relativePath of changedFiles) {
      const entry = before.get(relativePath);
      const targetPath = path.join(this.workingDirectory, relativePath);
      const backupPath = path.join(this.backupDirectory, snapshotId, "files", relativePath);

      if (entry?.existed) {
        fs.mkdirSync(path.dirname(targetPath), { recursive: true, mode: 0o755 });
        fs.copyFileSync(backupPath, targetPath);
        restoredFiles.push(relativePath);
        continue;
      }

      if (fs.existsSync(targetPath)) {
        fs.rmSync(targetPath, { force: true, recursive: true });
        removedFiles.push(relativePath);
      }
    }

    return {
      snapshotId,
      restoredFiles,
      removedFiles
    };
  }

  private writeMetadata(metadata: SnapshotMetadata): void {
    const snapshotDirectory = path.join(this.backupDirectory, metadata.id);
    fs.mkdirSync(snapshotDirectory, { recursive: true, mode: 0o700 });
    fs.writeFileSync(metadataPath(snapshotDirectory), `${JSON.stringify(metadata, null, 2)}\n`, {
      mode: 0o600
    });
  }
}
