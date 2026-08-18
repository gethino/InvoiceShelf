# InvoiceShelf v2 Local Setup

## Goal

Run stable InvoiceShelf 2.4.2 through Herd with separate DBngin MariaDB databases, while maintaining a fork-based staging and production Git workflow.

## Tasks

- [x] Create `master` at tag `2.4.2`; configure the official repository as `upstream` and the `gethino` fork as `origin`.
- [x] Install Composer and frontend dependencies from the committed lockfiles.
- [x] Configure `.env` for local Herd access and `invoiceshelf_prod` on DBngin.
- [x] Configure PHPUnit and `.env.testing` for `invoiceshelf_testing` on DBngin.
- [x] Create both databases, initialize the application, and build frontend assets.
- [x] Create `staging` and `production` branches from the verified stable baseline.
- [x] Verify the application version, database isolation, test execution, and Git topology.

## Done When

- [x] Herd can serve the application with the production-data database.
- [x] Tests connect only to the testing database.
- [x] `master`, `staging`, and `production` exist with the intended remotes.
