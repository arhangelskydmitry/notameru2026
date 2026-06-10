import pino from "pino";

const baseOptions = {
  level: process.env.LOG_LEVEL ?? "info"
};

export const logger =
  process.env.NODE_ENV === "production"
    ? pino(baseOptions)
    : pino({
        ...baseOptions,
        transport: {
          target: "pino/file",
          options: { destination: 1 }
        }
      });
