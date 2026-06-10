export interface MentorConsultResponse {
  ok: boolean;
  answer?: string;
  error?: string;
}

export interface MentorDialogResponse {
  ok: boolean;
  task?: string;
  done?: boolean;
  summary?: string;
  error?: string;
}

export interface MentorDialogPlanRequest {
  chatId: string;
  goal?: string;
  context?: string;
  previousTasks?: Array<{ task: string; result?: string; review?: string }>;
}

export interface MentorDialogReviewRequest {
  chatId: string;
  task: string;
  result: string;
  context?: string;
  previousTasks?: Array<{ task: string; result?: string; review?: string }>;
}

export class IikoMentorClient {
  constructor(private readonly baseUrl: string) {}

  async consult(question: string, context?: string): Promise<string> {
    const response = await fetch(new URL("/consult", this.baseUrl), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        question,
        ...(context ? { context } : {})
      })
    });

    if (!response.ok) {
      throw new Error(`Наставник IIKO не ответил: HTTP ${response.status}`);
    }

    const payload = (await response.json()) as MentorConsultResponse;

    if (!payload.ok || !payload.answer) {
      throw new Error(payload.error ?? "Наставник IIKO вернул пустой ответ");
    }

    return payload.answer;
  }

  async dialogPlan(request: MentorDialogPlanRequest): Promise<MentorDialogResponse> {
    return this.postDialog("/dialog/plan", request);
  }

  async dialogReview(request: MentorDialogReviewRequest): Promise<MentorDialogResponse> {
    return this.postDialog("/dialog/review", request);
  }

  private async postDialog(
    path: string,
    body: MentorDialogPlanRequest | MentorDialogReviewRequest
  ): Promise<MentorDialogResponse> {
    const response = await fetch(new URL(path, this.baseUrl), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      throw new Error(`Наставник IIKO не ответил: HTTP ${response.status}`);
    }

    const payload = (await response.json()) as MentorDialogResponse;

    if (!payload.ok) {
      throw new Error(payload.error ?? "Наставник IIKO вернул ошибку в режиме /диалог");
    }

    return payload;
  }
}
