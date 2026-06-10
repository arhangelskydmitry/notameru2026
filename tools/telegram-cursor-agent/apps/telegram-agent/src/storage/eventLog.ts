import { appendJsonLine } from "./fileStore.js";

export class EventLog {
  constructor(private readonly filePath: string) {}

  write(event: string, payload: Record<string, unknown> = {}): void {
    appendJsonLine(this.filePath, {
      ts: new Date().toISOString(),
      event,
      ...payload
    });
  }
}
