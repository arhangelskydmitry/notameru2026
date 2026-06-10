export interface IikoControlStatus {
  ok: boolean;
  service: string;
  uptimeSeconds: number;
  incidents: number;
}

export interface Incident {
  id: string;
  level: string;
  status: string;
  title: string;
  description: string;
}

export class IikoControlClient {
  constructor(private readonly baseUrl: string) {}

  async getStatus(): Promise<IikoControlStatus> {
    return this.getJson<IikoControlStatus>("/status");
  }

  async listIncidents(): Promise<Incident[]> {
    const response = await this.getJson<{ incidents: Incident[] }>("/incidents");
    return response.incidents;
  }

  private async getJson<T>(path: string): Promise<T> {
    const response = await fetch(new URL(path, this.baseUrl));

    if (!response.ok) {
      throw new Error(`iiko Control API ${path} failed with ${response.status}`);
    }

    return (await response.json()) as T;
  }
}
