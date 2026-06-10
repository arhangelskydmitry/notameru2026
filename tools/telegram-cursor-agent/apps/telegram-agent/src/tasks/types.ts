export type TaskStatus = "queued" | "running" | "succeeded" | "failed" | "rejected" | "paused";

export type TaskKind = "command" | "note" | "agent";

export interface Task {
  id: string;
  kind: TaskKind;
  input: string;
  status: TaskStatus;
  createdBy: number;
  createdAt: string;
  updatedAt: string;
  startedAt?: string;
  finishedAt?: string;
  exitCode?: number | null;
  runId?: string;
  backupId?: string;
  changedFiles?: string[];
  diffSummary?: string;
  output?: string;
  error?: string;
}

export interface TaskStore {
  create(task: Omit<Task, "id" | "createdAt" | "updatedAt" | "status">): Task;
  update(id: string, patch: Partial<Omit<Task, "id" | "createdAt">>): Task | undefined;
  get(id: string): Task | undefined;
  list(limit?: number): Task[];
  countActive(): number;
}
