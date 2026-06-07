#!/usr/bin/env node
import { execSync } from "child_process";

type DeployTarget = "full" | "web" | "nginx" | "db";

const ALLOWED_TARGETS: DeployTarget[] = ["full", "web", "nginx", "db"];
const DEFAULT_BRANCH = "main";
const DEFAULT_TARGET: DeployTarget = "full";
const WORKFLOW_NAME = "Deploy to Production";

function run(command: string): string {
  return execSync(command, { encoding: "utf8", stdio: ["inherit", "pipe", "pipe"] }).trim();
}

function fail(message: string): never {
  console.error(message);
  process.exit(1);
}

function parseReleaseArg(arg?: string): { ref: string; target: DeployTarget } {
  if (!arg) {
    return { ref: DEFAULT_BRANCH, target: DEFAULT_TARGET };
  }
  const [rawRef, rawTarget] = arg.split(":");
  const ref = (rawRef || DEFAULT_BRANCH).trim();
  const target = (rawTarget || DEFAULT_TARGET).trim() as DeployTarget;
  if (!ref) fail("Branch/tag cannot be empty.");
  if (!ALLOWED_TARGETS.includes(target)) fail(`Unknown deploy target: ${target}`);
  return { ref, target };
}

function ensureGhReady() {
  try {
    run("gh --version");
    run("gh auth status");
  } catch {
    fail("GitHub CLI missing or not authenticated. Run: gh auth login");
  }
}

async function main() {
  const { ref, target } = parseReleaseArg(process.argv[2]);
  ensureGhReady();
  run(
    `gh workflow run "${WORKFLOW_NAME}" -f ref=${JSON.stringify(ref)} -f deploy_target=${JSON.stringify(target)}`
  );
  console.log(`Triggered deploy for ref="${ref}" target="${target}"`);
}

main();
