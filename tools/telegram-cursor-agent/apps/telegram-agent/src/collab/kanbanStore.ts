import { readJsonFile, writeJsonFile } from "../storage/fileStore.js";

export type KanbanColumn = "todo" | "doing" | "done" | "blockers";

export interface KanbanItem {
  id: string;
  text: string;
  column: KanbanColumn;
  createdBy: number;
  createdAt: string;
  updatedAt: string;
}

export class KanbanStore {
  private readonly items = new Map<string, KanbanItem>();

  constructor(private readonly kanbanFile: string) {
    for (const item of readJsonFile<KanbanItem[]>(this.kanbanFile, [])) {
      this.items.set(item.id, item);
    }
  }

  add(column: KanbanColumn, text: string, createdBy: number): KanbanItem {
    const now = new Date().toISOString();
    const item: KanbanItem = {
      id: crypto.randomUUID(),
      text,
      column,
      createdBy,
      createdAt: now,
      updatedAt: now
    };

    this.items.set(item.id, item);
    this.persist();
    return item;
  }

  move(idOrPrefix: string, column: KanbanColumn): KanbanItem | undefined {
    const item = this.get(idOrPrefix);

    if (!item) {
      return undefined;
    }

    item.column = column;
    item.updatedAt = new Date().toISOString();
    this.persist();
    return item;
  }

  get(idOrPrefix: string): KanbanItem | undefined {
    return (
      this.items.get(idOrPrefix) ??
      [...this.items.values()].find((item) => item.id.startsWith(idOrPrefix))
    );
  }

  list(column?: KanbanColumn): KanbanItem[] {
    return [...this.items.values()]
      .filter((item) => !column || item.column === column)
      .sort((left, right) => right.updatedAt.localeCompare(left.updatedAt));
  }

  private persist(): void {
    writeJsonFile(this.kanbanFile, [...this.items.values()]);
  }
}

export function formatKanban(items: KanbanItem[], title = "Канбан"): string {
  if (items.length === 0) {
    return `${title}: пусто.`;
  }

  return [
    title,
    ...items.map((item) => `${item.id.slice(0, 8)} | ${item.column} | ${item.text}`)
  ].join("\n");
}
