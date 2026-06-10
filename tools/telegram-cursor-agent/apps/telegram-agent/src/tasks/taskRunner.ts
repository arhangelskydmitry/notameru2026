import { spawn } from "node:child_process";
import type { AppConfig } from "../config.js";
import { logger } from "../logger.js";
import { getAllowedCommand } from "./commandPolicy.js";
import type { Task, TaskStore } from "./types.js";

function tailText(value: string, maxChars: number): string {
  if (value.length <= maxChars) {
    return value;
  }

  return value.slice(value.length - maxChars);
}

export class TaskRunner {
  constructor(
    private readonly store: TaskStore,
    private readonly config: Pick<
      AppConfig,
      "workingDirectory" | "commandTimeoutMs" | "logTailChars"
    >
  ) {}

  async runCommand(task: Task): Promise<Task> {
    const allowedCommand = getAllowedCommand(task.input);

    if (!allowedCommand) {
      logger.warn({ taskId: task.id, input: task.input }, "Rejected command");

      return this.store.update(task.id, {
        status: "rejected",
        finishedAt: new Date().toISOString(),
        error: "Command is not allowed. Use /help to see the whitelist."
      })!;
    }

    this.store.update(task.id, {
      status: "running",
      startedAt: new Date().toISOString()
    });

    logger.info(
      {
        taskId: task.id,
        command: allowedCommand.command,
        args: allowedCommand.args
      },
      "Starting command task"
    );

    return new Promise((resolve) => {
      const child = spawn(allowedCommand.command, allowedCommand.args, {
        cwd: this.config.workingDirectory,
        shell: false,
        env: process.env
      });

      let output = "";
      let didTimeout = false;

      const timeout = setTimeout(() => {
        didTimeout = true;
        child.kill("SIGTERM");
      }, this.config.commandTimeoutMs);

      child.stdout.on("data", (chunk: Buffer) => {
        output += chunk.toString("utf8");
        output = tailText(output, this.config.logTailChars);
      });

      child.stderr.on("data", (chunk: Buffer) => {
        output += chunk.toString("utf8");
        output = tailText(output, this.config.logTailChars);
      });

      child.on("error", (error) => {
        clearTimeout(timeout);

        const updated = this.store.update(task.id, {
          status: "failed",
          finishedAt: new Date().toISOString(),
          output: tailText(output, this.config.logTailChars),
          error: error.message
        })!;

        logger.error({ taskId: task.id, error }, "Command task failed to start");
        resolve(updated);
      });

      child.on("close", (exitCode) => {
        clearTimeout(timeout);

        const status = exitCode === 0 && !didTimeout ? "succeeded" : "failed";
        const error = didTimeout
          ? `Command timed out after ${this.config.commandTimeoutMs}ms`
          : exitCode === 0
            ? undefined
            : `Command exited with code ${exitCode ?? "unknown"}`;

        const update = {
          status,
          exitCode,
          finishedAt: new Date().toISOString(),
          output: tailText(output || "(no output)", this.config.logTailChars)
        } satisfies Parameters<TaskStore["update"]>[1];

        const updated = this.store.update(task.id, error ? { ...update, error } : update)!;

        logger.info({ taskId: task.id, status, exitCode }, "Command task finished");
        resolve(updated);
      });
    });
  }
}
