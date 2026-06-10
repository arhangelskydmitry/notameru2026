import { readJsonFile, writeJsonFile } from "../storage/fileStore.js";

export interface PendingApproval {
  id: string;
  input: string;
  reason: string;
  requestedBy: number;
  createdAt: string;
}

export class ApprovalStore {
  private readonly approvals = new Map<string, PendingApproval>();

  constructor(private readonly approvalsFile: string) {
    for (const approval of readJsonFile<PendingApproval[]>(this.approvalsFile, [])) {
      this.approvals.set(approval.id, approval);
    }
  }

  create(approval: Omit<PendingApproval, "id" | "createdAt">): PendingApproval {
    const created: PendingApproval = {
      ...approval,
      id: crypto.randomUUID(),
      createdAt: new Date().toISOString()
    };

    this.approvals.set(created.id, created);
    this.persist();
    return created;
  }

  get(idOrPrefix: string): PendingApproval | undefined {
    return (
      this.approvals.get(idOrPrefix) ??
      [...this.approvals.values()].find((approval) =>
        approval.id.startsWith(idOrPrefix)
      )
    );
  }

  delete(id: string): boolean {
    const deleted = this.approvals.delete(id);

    if (deleted) {
      this.persist();
    }

    return deleted;
  }

  list(limit = 10): PendingApproval[] {
    return [...this.approvals.values()]
      .sort((left, right) => right.createdAt.localeCompare(left.createdAt))
      .slice(0, limit);
  }

  private persist(): void {
    writeJsonFile(this.approvalsFile, [...this.approvals.values()]);
  }
}
