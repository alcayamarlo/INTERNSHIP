# Skill-Bridge Documentation

This index lists documentation files currently maintained in the repository.

## Start here

- [README.md](README.md) - project scope, requirements, setup, verification,
  queue worker, and security notes.
- [GETTING_STARTED.md](GETTING_STARTED.md) - Windows local installation and
  first-use walkthrough.
- [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) - implemented role capabilities,
  architecture, verification snapshot, and limitations.

## Engineering and operations

- [BACKEND_AUDIT.md](BACKEND_AUDIT.md) - resolved backend reliability areas
  and known test/deployment gaps.
- [COMMANDS_REFERENCE.md](COMMANDS_REFERENCE.md) - additional Laravel,
  frontend, testing, and maintenance commands.

## Scope notes

- Matching is deterministic and rule-based; the application does not use an
  AI/ML matching model.
- The system supports internship opportunities, not freelance listings.
- SMS sending and guaranteed real-time delivery are not implemented.
- Email delivery requires mail transport configuration. Queued jobs require a
  running queue worker.
