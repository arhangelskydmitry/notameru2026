export type TaskStatus = "queued" | "running" | "succeeded" | "failed" | "rejected";

export interface Task {
  id: string;
  kind: "agent";
  input: string;
  status: TaskStatus;
  createdBy: number;
  createdAt: string;
  updatedAt: string;
  startedAt?: string;
  finishedAt?: string;
  runId?: string;
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
