export interface AllowedCommand {
  key: string;
  description: string;
  command: string;
  args: string[];
}

export const allowedCommands: AllowedCommand[] = [
  {
    key: "pwd",
    description: "Show current working directory",
    command: "pwd",
    args: []
  },
  {
    key: "git status --short",
    description: "Show short Git status",
    command: "git",
    args: ["status", "--short"]
  },
  {
    key: "npm test",
    description: "Run project tests",
    command: "npm",
    args: ["test"]
  },
  {
    key: "npm run build",
    description: "Build the project",
    command: "npm",
    args: ["run", "build"]
  },
  {
    key: "npm run lint",
    description: "Run lint script",
    command: "npm",
    args: ["run", "lint"]
  }
];

export function getAllowedCommand(input: string): AllowedCommand | undefined {
  const normalizedInput = input.trim().replace(/\s+/g, " ");

  return allowedCommands.find((allowedCommand) => allowedCommand.key === normalizedInput);
}

export function formatAllowedCommands(): string {
  return allowedCommands
    .map((allowedCommand) => `/${allowedCommand.key} - ${allowedCommand.description}`)
    .join("\n");
}
