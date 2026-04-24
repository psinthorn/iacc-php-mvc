# iACC AI Agent Team

## Team Members

| Agent | File | Model | Best For |
|-------|------|-------|----------|
| Product Manager | `prompts/agent-pm.md` | claude-opus-4-6 | Feature specs, user stories |
| Backend Developer | `prompts/agent-backend.md` | claude-sonnet-4-6 | PHP/MySQL code, migrations |
| Frontend Developer | `prompts/agent-frontend.md` | claude-sonnet-4-6 | Views, Bootstrap UI |
| QA Tester | `prompts/agent-qa.md` | claude-sonnet-4-6 | Code review, test cases |
| DevOps Engineer | `prompts/agent-devops.md` | claude-sonnet-4-6 | Deployment, cPanel, scripts |
| UI/UX Designer | `prompts/agent-designer.md` | claude-opus-4-6 | Wireframes, v0.dev prompts |
| Marketing | `prompts/agent-marketing.md` | claude-opus-4-6 | Copy, social media, emails |
| Customer Support | `prompts/agent-support.md` | claude-haiku-4-5-20251001 | User questions, FAQs |
| Project Tracker  | `prompts/agent-tracker.md` | claude-opus-4-6 | Milestones, tasks, timeline summaries |
| Database Analyst | `prompts/agent-dba.md`     | claude-opus-4-6 | Schema design, query optimization, migrations, reporting SQL |

---

## How to Use Each Agent

### Option A — Claude Code (this tool)
Paste the system prompt from any agent file, then give your task:
```
[paste contents of agent-pm.md]

Task: Write a feature spec for "bulk booking import via CSV"
```

### Option B — Claude API (PHP)
Use `agent-runner.php` to call any agent programmatically:
```php
$runner = new AgentRunner();
$response = $runner->run('pm', 'Write a spec for CSV bulk import');
```

### Option C — claude.ai Projects
1. Go to claude.ai → Projects → Create Project
2. Create one project per agent
3. Paste the system prompt as the project instructions
4. Use that project for all tasks for that role

---

## Feature Development Workflow

```
1. PM Agent      → writes feature spec + acceptance criteria
2. Designer      → creates wireframe / v0.dev prompt
3. Backend Dev   → implements controller, model, migration
4. Frontend Dev  → builds the view
5. QA Agent      → reviews code, writes test cases
6. DevOps        → prepares deployment SQL + file list
7. Support       → adds FAQ entry for the feature
8. Marketing     → announces the feature
```

---

## Quick Prompts (Copy & Use)

### Ask PM Agent
> "Write a feature spec for: [your feature idea]"

### Ask Backend Agent
> "Implement the backend for: [spec from PM]. Follow our PHP MVC conventions in /app/Controllers and /app/Models."

### Ask QA Agent
> "Review this code for bugs, security issues, and edge cases: [paste code]"

### Ask DevOps Agent
> "Create a cPanel deployment checklist for these changed files: [list files]"

### Ask Designer Agent
> "Write a v0.dev prompt for a Bootstrap 5 view that shows: [describe the screen]"

### Ask Tracker Agent
> "Create a milestone plan for: [your feature idea]"
> "Give me a sprint summary of what we've built so far"
> "Show me a timeline from [feature] start to production"

### Ask DBA Agent
> "Review this query for performance: [paste SQL]"
> "Design a schema for: [describe the feature]"
> "Write a migration to add [column] to [table]"
> "Write a report query for: [booking revenue by agent per month]"
