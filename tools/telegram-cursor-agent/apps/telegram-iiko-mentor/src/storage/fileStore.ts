import fs from "node:fs";
import path from "node:path";

export function ensureParentDirectory(filePath: string): void {
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
}

export function readJsonFile<T>(filePath: string, fallback: T): T {
  try {
    if (!fs.existsSync(filePath)) {
      return fallback;
    }

    return JSON.parse(fs.readFileSync(filePath, "utf8")) as T;
  } catch {
    return fallback;
  }
}

export function writeJsonFile(filePath: string, value: unknown): void {
  ensureParentDirectory(filePath);
  const tempPath = `${filePath}.${process.pid}.tmp`;
  fs.writeFileSync(tempPath, `${JSON.stringify(value, null, 2)}\n`, {
    mode: 0o600
  });
  fs.renameSync(tempPath, filePath);
}

export function appendJsonLine(filePath: string, value: unknown): void {
  ensureParentDirectory(filePath);
  fs.appendFileSync(filePath, `${JSON.stringify(value)}\n`, { mode: 0o600 });
}
