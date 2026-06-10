import type { Task, TaskStore } from "./types.js";
import { readJsonFile, writeJsonFile } from "../storage/fileStore.js";

export class PersistentTaskStore implements TaskStore {
  private readonly tasks = new Map<string, Task>();

  constructor(private readonly stateFile: string) {
    let recovered = false;

    for (const task of readJsonFile<Task[]>(this.stateFile, [])) {
      if (task.status === "running") {
        this.tasks.set(task.id, {
          ...task,
          status: "failed",
          finishedAt: new Date().toISOString(),
          error:
            task.error ??
            "Task was marked failed during service startup because it was active in a previous process."
        });
        recovered = true;
      } else if (task.status === "queued") {
        this.tasks.set(task.id, {
          ...task,
          status: "paused",
          error:
            task.error ??
            "Task was paused during service startup. Re-send it when you are ready to run it."
        });
        recovered = true;
      } else {
        this.tasks.set(task.id, task);
      }
    }

    if (recovered) {
      this.persist();
    }
  }

  create(task: Omit<Task, "id" | "createdAt" | "updatedAt" | "status">): Task {
    const now = new Date().toISOString();
    const createdTask: Task = {
      ...task,
      id: crypto.randomUUID(),
      status: "queued",
      createdAt: now,
      updatedAt: now
    };

    this.tasks.set(createdTask.id, createdTask);
    this.persist();
    return createdTask;
  }

  update(id: string, patch: Partial<Omit<Task, "id" | "createdAt">>): Task | undefined {
    const current = this.tasks.get(id);

    if (!current) {
      return undefined;
    }

    const updated: Task = {
      ...current,
      ...patch,
      updatedAt: new Date().toISOString()
    };

    this.tasks.set(id, updated);
    this.persist();
    return updated;
  }

  get(id: string): Task | undefined {
    return this.tasks.get(id);
  }

  list(limit = 10): Task[] {
    return [...this.tasks.values()]
      .sort((left, right) => right.createdAt.localeCompare(left.createdAt))
      .slice(0, limit);
  }

  countActive(): number {
    return [...this.tasks.values()].filter((task) =>
      ["queued", "running"].includes(task.status)
    ).length;
  }

  private persist(): void {
    writeJsonFile(this.stateFile, [...this.tasks.values()]);
  }
}
