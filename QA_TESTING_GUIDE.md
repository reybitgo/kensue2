# Kensue Binary System — QA Testing Guide

**Version:** 1.0 · **Environment:** `http://localhost/kensue/` · **Database:** `kensue_db`

---

## HOW TO USE THIS DOCUMENT

This guide walks you through every feature of the system in order. Each test has:

- **Steps** — exactly what to click/type
- **Expected Result** — what should happen if it's working
- **Pass/Fail** — a checkbox you can tick as you go

You do not need any technical background to follow this guide. Just follow the steps exactly as written.

### Test Status Legend

```
[ ] — Not tested yet
[P] — PASS
[F] — FAIL  (note what went wrong in the Notes column)
[S] — SKIP  (not applicable for this environment)
```

### Before You Start

1. Make sure Laragon is running (Apache = green, MySQL = green)
2. Open your browser and go to `http://localhost/kensue/`
3. Open a second tab — you will need it later
4. Have a notepad ready to write down any failures

---

## TEST ENVIRONMENT SETUP

### Accounts You Will Create During Testing

Keep this table filled in as you go. You will need these later.

| Role                         | Username  | Password     | Reg Code Used  |
| ---------------------------- | --------- | ------------ | -------------- |
| Admin (pre-existing)         | `admin`   | `Admin@1234` | —              |
| Member 1 (Sponsor)           | `member1` | `Test@1234`  | _(from admin)_ |
| Member 2 (Left of Member 1)  | `member2` | `Test@1234`  | _(from admin)_ |
| Member 3 (Right of Member 1) | `member3` | `Test@1234`  | _(from admin)_ |
| Member 4 (Left of Member 2)  | `member4` | `Test@1234`  | _(from admin)_ |
| Member 5 (Right of Member 2) | `member5` | `Test@1234`  | _(from admin)_ |

> These accounts form the binary tree used throughout all tests.

---

## SECTION 1 — AUTHENTICATION

### TC-001 · Login Page Display

**What we are testing:** The login page loads correctly with all visual elements.

| #   | Step                                      | Expected Result                                   | Status |
| --- | ----------------------------------------- | ------------------------------------------------- | ------ |
| 1   | Go to `http://localhost/kensue/`          | Browser redirects to `/?page=login`               | [P]    |
| 2   | Look at the page title in the browser tab | Shows **"Login — Kensue"**                        | [P]    |
| 3   | Check the favicon in the browser tab      | Green hexagon logo is visible                     | [P]    |
| 4   | Check the top of the login card           | Kensue logo image is visible                      | [P]    |
| 5   | Check the card heading                    | Shows **"Kensue"**                                | [P]    |
| 6   | Check the subtext                         | Shows **"Build Your Network. Grow Your Income."** | [P]    |
| 7   | Check the form fields                     | Username field and Password field are visible     | [P]    |
| 8   | Check the button                          | **"Sign In"** button is visible                   | [P]    |
| 9   | Check the footer link                     | **"Register with a code →"** link is visible      | [P]    |

---

### TC-002 · Login — Invalid Credentials

**What we are testing:** The system rejects wrong passwords correctly.

| #   | Step                                  | Expected Result                                | Status |
| --- | ------------------------------------- | ---------------------------------------------- | ------ |
| 1   | Type `admin` in the Username field    | Text appears in field                          | [P]    |
| 2   | Type `wrongpassword` in Password      | Text appears (as dots)                         | [P]    |
| 3   | Click **Sign In**                     | Page reloads                                   | [P]    |
| 4   | Look for an error message             | Red alert: **"Invalid username or password."** | [P]    |
| 5   | Check you are still on the login page | URL still shows `/?page=login`                 | [P]    |

---

### TC-003 · Login — Show/Hide Password

**What we are testing:** The eye icon toggles password visibility.

| #   | Step                                                     | Expected Result                  | Status |
| --- | -------------------------------------------------------- | -------------------------------- | ------ |
| 1   | Type anything in the Password field                      | Text appears as dots             | [P]    |
| 2   | Click the 👁 eye icon on the right of the password field | Password text becomes visible    | [P]    |
| 3   | Click the eye icon again                                 | Password text hides again (dots) | [P]    |

---

### TC-004 · Login — Rate Limiting

**What we are testing:** After 5 failed attempts, login is temporarily blocked.

| #   | Step                                            | Expected Result                                                | Status |
| --- | ----------------------------------------------- | -------------------------------------------------------------- | ------ |
| 1   | Enter `admin` and any wrong password            | Error: Invalid credentials                                     | [P]    |
| 2   | Repeat 4 more times (5 total failures)          | Same error each time                                           | [P]    |
| 3   | On the 6th attempt with wrong password          | Error: **"Too many failed attempts. Please wait 15 minutes."** | [P]    |
| 4   | Try with the correct password immediately after | Still blocked                                                  | [P]    |

> **Note:** To reset this for further testing, close the browser and reopen, or wait 15 minutes.

---

### TC-005 · Login — Successful Admin Login

**What we are testing:** Admin can log in and lands on the correct page.

| #   | Step                             | Expected Result                        | Status |
| --- | -------------------------------- | -------------------------------------- | ------ |
| 1   | Go to `http://localhost/kensue/` | Login page shows                       | [P]    |
| 2   | Type `admin` in Username         | —                                      | [P]    |
| 3   | Type `Admin@1234` in Password    | —                                      | [P]    |
| 4   | Click **Sign In**                | Page redirects                         | [P]    |
| 5   | Check the URL                    | Shows `/?page=admin`                   | [P]    |
| 6   | Check the page title             | Shows **"Admin Dashboard — Kensue"**   | [P]    |
| 7   | Check the sidebar                | Shows **"ADMIN PANEL"** below the logo | [P]    |

---

### TC-006 · Logout

**What we are testing:** Logout clears the session and redirects to login.

| #   | Step                                                                          | Expected Result             | Status |
| --- | ----------------------------------------------------------------------------- | --------------------------- | ------ |
| 1   | While logged in as admin, click the ⏻ power icon at the bottom of the sidebar | —                           | [P]    |
| 2   | Check the URL                                                                 | Redirects to `/?page=login` | [P]    |
| 3   | Try visiting `http://localhost/kensue/?page=admin` directly                   | Redirects back to login     | [P]    |
| 4   | Try visiting `http://localhost/kensue/?page=dashboard` directly               | Redirects back to login     | [P]    |

---

## SECTION 2 — ADMIN: PACKAGE MANAGEMENT

> Log in as `admin` before starting this section.

### TC-007 · View Packages Page

| #   | Step                                | Expected Result                                              | Status |
| --- | ----------------------------------- | ------------------------------------------------------------ | ------ |
| 1   | In the sidebar, click **Packages**  | Page loads at `/?page=admin_packages`                        | [P]    |
| 2   | Check the packages list on the left | **"Starter"** package is listed                              | [P]    |
| 3   | Check the Starter package details   | Entry Fee: ₱10,000.00 · Pair Bonus: ₱2,000.00 · Cap: 3 pairs | [P]    |
| 4   | Check the form on the right         | Empty **"New Package"** form is visible                      | [P]    |

---

### TC-008 · Create a New Package

**What we are testing:** Admin can create a package with all 10 indirect levels.

| #   | Step                                                  | Expected Result               | Status |
| --- | ----------------------------------------------------- | ----------------------------- | ------ |
| 1   | On the Packages page, fill in **Package Name**: `Pro` | —                             | [P]    |
| 2   | Fill **Entry Fee**: `20000`                           | —                             | [P]    |
| 3   | Fill **Pairing Bonus**: `4000`                        | —                             | [P]    |
| 4   | Fill **Daily Pair Cap**: `5`                          | —                             | [P]    |
| 5   | Fill **Direct Referral Bonus**: `1000`                | —                             | [P]    |
| 6   | Fill Level 1: `600`, Level 2: `400`, Level 3: `300`   | —                             | [P]    |
| 7   | Fill Levels 4–10 with any values (e.g. `200` each)    | —                             | [P]    |
| 8   | Set Status to **Active**                              | —                             | [P]    |
| 9   | Click **➕ Create Package**                           | —                             | [P]    |
| 10  | Check the flash message                               | Green: **"Package created."** | [P]    |
| 11  | Check the packages list                               | **"Pro"** package now appears | [P]    |

---

### TC-009 · Edit an Existing Package

| #   | Step                                           | Expected Result                                        | Status |
| --- | ---------------------------------------------- | ------------------------------------------------------ | ------ |
| 1   | Click **Edit** next to the **Starter** package | URL changes to `/?page=admin_packages&edit=1`          | [P]    |
| 2   | Check the form title                           | Shows **"✏️ Edit Package"**                            | [P]    |
| 3   | Check all fields are pre-filled                | Name, fees, bonus, cap and all 10 levels are populated | [P]    |
| 4   | Change **Daily Pair Cap** to `4`               | —                                                      | [P]    |
| 5   | Click **💾 Update Package**                    | Flash message: **"Package updated."**                  | [P]    |
| 6   | Click Edit again on Starter                    | Daily Pair Cap now shows `4`                           | [P]    |
| 7   | Change it back to `3` and save                 | Restored to original                                   | [P]    |

---

## SECTION 3 — ADMIN: REGISTRATION CODE MANAGEMENT

### TC-010 · View Codes Page

| #   | Step                               | Expected Result                                   | Status |
| --- | ---------------------------------- | ------------------------------------------------- | ------ |
| 1   | Click **Reg Codes** in the sidebar | Page loads at `/?page=admin_codes`                | [P]    |
| 2   | Check the 4 stat cards at the top  | Total, Unused, Used/Sold, Revenue cards visible   | [P]    |
| 3   | Check the **"Unused"** count       | Shows at least **1** (the demo code from install) | [P]    |
| 4   | Check the codes table              | At least one code `DEMO-STAR-TKIT` is listed      | [P]    |

---

### TC-011 · Generate Registration Codes

**What we are testing:** Admin can generate a batch of codes.

| #   | Step                                                            | Expected Result                                     | Status |
| --- | --------------------------------------------------------------- | --------------------------------------------------- | ------ |
| 1   | On the Codes page, select **Starter** from the Package dropdown | Price field auto-fills with `10500`                 | [P]    |
| 2   | Change Quantity to `5`                                          | —                                                   | [P]    |
| 3   | Confirm price is `10500` (or change it)                         | —                                                   | [P]    |
| 4   | Leave Expiry Date blank                                         | —                                                   | [P]    |
| 5   | Click **🎟️ Generate Codes** · Click OK on the confirmation      | —                                                   | [P]    |
| 6   | Check flash message                                             | Green: **"5 code(s) generated successfully."**      | [P]    |
| 7   | Check the codes table                                           | 5 new codes with format `XXXX-XXXX-XXXX` are listed | [P]    |
| 8   | Check all new codes have status                                 | **"Unused"** badge in green                         | [P]    |
| 9   | Note down 5 codes for member registration tests                 | Write them in the table at the top of this document | [P]    |

---

### TC-012 · Export Codes to CSV

| #   | Step                                                  | Expected Result                                                           | Status |
| --- | ----------------------------------------------------- | ------------------------------------------------------------------------- | ------ |
| 1   | On the Codes page, click **📥 Export to CSV / Excel** | Browser downloads a file                                                  | [P]    |
| 2   | Check the downloaded file name                        | Format: `reg_codes_YYYY-MM-DD.csv`                                        | [P]    |
| 3   | Open the file in Excel or Notepad                     | Contains columns: Code, Package, Price, Status, Created, Expires, Used By | [P]    |
| 4   | Check the data                                        | All codes are present with correct details                                | [P]    |

---

### TC-013 · Print Codes

| #   | Step                                                    | Expected Result                             | Status |
| --- | ------------------------------------------------------- | ------------------------------------------- | ------ |
| 1   | On the Codes page, click **🖨️ Print Codes (PDF-ready)** | Browser print dialog opens                  | [P]    |
| 2   | Look at the print preview                               | Codes appear in a 3-column grid             | [P]    |
| 3   | Each code card shows                                    | Code, package name, price, expiry           | [P]    |
| 4   | Press Escape to cancel the print                        | Print dialog closes, page returns to normal | [P]    |

---

### TC-014 · Filter Codes

| #   | Step                                                                      | Expected Result                        | Status |
| --- | ------------------------------------------------------------------------- | -------------------------------------- | ------ |
| 1   | On the Codes page, select **"Unused"** from the Filter by Status dropdown | Page reloads                           | [P]    |
| 2   | Check results                                                             | Only unused codes are shown            | [P]    |
| 3   | Select **"All Statuses"** again                                           | All codes visible again                | [P]    |
| 4   | Select **Starter** from Filter by Package                                 | Only Starter package codes shown       | [P]    |
| 5   | Click **✕ Clear filter** link                                             | All filters cleared, all codes visible | [S]    |

---

## SECTION 4 — REGISTRATION FLOW

> Open a new incognito/private browser window for this section so you are not logged in.

### TC-015 · Register Page Display

| #   | Step                                           | Expected Result                                            | Status |
| --- | ---------------------------------------------- | ---------------------------------------------------------- | ------ |
| 1   | Go to `http://localhost/kensue/?page=register` | Registration page loads                                    | [ ]    |
| 2   | Check the step progress bar                    | Shows 3 steps: **Validate Code · Account Setup · Confirm** | [ ]    |
| 3   | Check Step 1 is active                         | **"Validate Code"** step is highlighted in blue            | [ ]    |

---

### TC-016 · Step 1 — Invalid Code Validation

| #   | Step                                    | Expected Result                                    | Status |
| --- | --------------------------------------- | -------------------------------------------------- | ------ |
| 1   | Type `AAAA-BBBB-CCCC` in the code field | Text appears formatted with dashes                 | [ ]    |
| 2   | Click **Validate**                      | —                                                  | [ ]    |
| 3   | Check hint below the field              | Red text: **"Code is invalid, used, or expired."** | [ ]    |
| 4   | Check the **Continue →** button         | Remains disabled (greyed out)                      | [ ]    |

---

### TC-017 · Step 1 — Auto Code Formatting

| #   | Step                                                     | Expected Result                                               | Status |
| --- | -------------------------------------------------------- | ------------------------------------------------------------- | ------ |
| 1   | Click the code field and type `abcdefghijkl` (no dashes) | Code auto-formats to `ABCD-EFGH-IJKL` (uppercase with dashes) | [ ]    |
| 2   | Try typing more than 12 characters                       | Input stops at 12 characters                                  | [ ]    |

---

### TC-018 · Step 1 — Valid Code Validation

| #   | Step                                           | Expected Result                                                               | Status |
| --- | ---------------------------------------------- | ----------------------------------------------------------------------------- | ------ |
| 1   | Clear the code field                           | —                                                                             | [ ]    |
| 2   | Enter one of the codes you generated in TC-011 | —                                                                             | [ ]    |
| 3   | Click **Validate**                             | —                                                                             | [ ]    |
| 4   | Check the green banner that appears            | Shows ✅ with **"Starter"** package name, entry fee, pairing bonus, daily cap | [ ]    |
| 5   | Check the hint                                 | Green: **"✓ Code is valid!"**                                                 | [ ]    |
| 6   | Check the **Continue →** button                | Now enabled (blue, clickable)                                                 | [ ]    |
| 7   | Click **Continue →**                           | Moves to Step 2 — Account Setup                                               | [ ]    |
| 8   | Check progress bar                             | Step 1 shows ✓ done, Step 2 is active                                         | [ ]    |

---

### TC-019 · Step 2 — Username Validation

| #   | Step                                         | Expected Result                                            | Status |
| --- | -------------------------------------------- | ---------------------------------------------------------- | ------ |
| 1   | In the Username field, type `ab` (too short) | Hint: **"At least 3 characters required."**                | [ ]    |
| 2   | Clear and type `admin` (already taken)       | After a short pause, hint: **"Username is taken."** in red | [ ]    |
| 3   | Field border turns red                       | —                                                          | [ ]    |
| 4   | Clear and type `member1`                     | After pause, hint: **"Username is available."** in green   | [ ]    |
| 5   | Field border turns green                     | —                                                          | [ ]    |

---

### TC-020 · Step 2 — Password Match Validation

| #   | Step                                                        | Expected Result                              | Status |
| --- | ----------------------------------------------------------- | -------------------------------------------- | ------ |
| 1   | In the **Password** field, type `Test@1234`                 | —                                            | [ ]    |
| 2   | In the **Confirm Password** field, type `Test@1235` (wrong) | Hint: **"✗ Passwords do not match."** in red | [ ]    |
| 3   | Correct Confirm Password to `Test@1234`                     | Hint: **"✓ Passwords match."** in green      | [ ]    |

---

### TC-021 · Step 2 — Sponsor Validation

| #   | Step                                   | Expected Result                                           | Status |
| --- | -------------------------------------- | --------------------------------------------------------- | ------ |
| 1   | In **Sponsor Username**, type `nobody` | After pause, hint: **"✗ Sponsor not found."** in red      | [ ]    |
| 2   | Clear and type `admin`                 | After pause, hint: **"✓ Sponsor @admin found."** in green | [ ]    |

---

### TC-022 · Step 2 — Binary Upline + Slot Check

| #   | Step                                         | Expected Result                                               | Status |
| --- | -------------------------------------------- | ------------------------------------------------------------- | ------ |
| 1   | In **Binary Upline Username**, type `admin`  | After pause, hint: **"✓ Found @admin"**                       | [ ]    |
| 2   | Check the slot status row that appears below | Shows **"↙ Left: ✓ Free"** and **"↘ Right: ✓ Free"** in green | [ ]    |
| 3   | Select **↙ Left** position                   | Position hint: **"✓ Left slot is available."** in green       | [ ]    |
| 4   | Check the position buttons                   | Both Left and Right are enabled                               | [ ]    |

---

### TC-023 · Step 2 → Step 3 — Review Screen

| #   | Step                                                         | Expected Result                                                                                      | Status |
| --- | ------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------- | ------ |
| 1   | With all Step 2 fields filled in validly, click **Review →** | Moves to Step 3                                                                                      | [ ]    |
| 2   | Check the Registration Summary card                          | Shows: Code, Package, Username (@member1), Sponsor (@admin), Binary Upline (@admin), Position (Left) | [ ]    |
| 3   | Check the warning box                                        | Yellow warning about binary position being permanent                                                 | [ ]    |
| 4   | Click **← Back**                                             | Returns to Step 2 with all fields still filled                                                       | [ ]    |
| 5   | Click **Review →** again                                     | Returns to Step 3                                                                                    | [ ]    |

---

### TC-024 · Complete Registration — Member 1

| #   | Step                                                | Expected Result                                                                                                                             | Status |
| --- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| 1   | On Step 3 review, click **✓ Complete Registration** | Button shows spinner: "Creating account…"                                                                                                   | [ ]    |
| 2   | Wait for redirect                                   | Redirects to `/?page=dashboard`                                                                                                             | [ ]    |
| 3   | Check the flash message                             | Green: **"Welcome! Your account has been created successfully."**                                                                           | [ ]    |
| 4   | Check the sidebar                                   | Shows `member1` username at the bottom                                                                                                      | [ ]    |
| 5   | Check the topbar balance                            | Shows **₱500.00** (direct referral bonus went to admin, not member1 — member1 has ₱0 unless admin paid direct ref to self which is skipped) | [ ]    |

> **Note:** After member1 registers, go back to admin and check the admin dashboard. Admin's ewallet should show the direct referral bonus if applicable.

---

### TC-025 · Register Members 2–5

Repeat the registration process for the remaining test members using **separate incognito windows** or after logging out.

| Member   | Username  | Sponsor | Binary Upline | Position | Expected Pairing Trigger                          |
| -------- | --------- | ------- | ------------- | -------- | ------------------------------------------------- |
| Member 2 | `member2` | `admin` | `member1`     | Left     | member1 gets 1 pair (left+right needed — not yet) |
| Member 3 | `member3` | `admin` | `member1`     | Right    | **member1 gets 1 pair** — ₱2,000 fires instantly  |
| Member 4 | `member4` | `admin` | `member2`     | Left     | No pair yet for member2                           |
| Member 5 | `member5` | `admin` | `member2`     | Right    | **member2 gets 1 pair** — ₱2,000 fires instantly  |

For each member:

| #   | Step                                               | Expected Result                       | Status |
| --- | -------------------------------------------------- | ------------------------------------- | ------ |
| 1   | Use a fresh unused code from TC-011                | Code validates ✅                     | [ ]    |
| 2   | Fill username, password, sponsor, upline, position | All fields validate ✅                | [ ]    |
| 3   | Complete registration                              | Redirects to dashboard                | [ ]    |
| 4   | Note the ewallet balance shown                     | Record it for commission verification | [ ]    |

---

### TC-026 · Slot Taken Validation

**What we are testing:** The system prevents placing two members in the same slot.

| #   | Step                                       | Expected Result                                | Status |
| --- | ------------------------------------------ | ---------------------------------------------- | ------ |
| 1   | Start a new registration with a fresh code | —                                              | [ ]    |
| 2   | On Step 2, set Binary Upline to `member1`  | Slot status shows: Left ✗ Taken, Right ✗ Taken | [ ]    |
| 3   | Try to select **Left** position            | Left radio button is disabled                  | [ ]    |
| 4   | Try to select **Right** position           | Right radio button is disabled                 | [ ]    |
| 5   | Click **Review →**                         | Should not proceed — both slots taken          | [ ]    |

---

## SECTION 5 — MEMBER DASHBOARD

> Log in as `member1` for this section.

### TC-027 · Dashboard KPI Cards

| #   | Step                              | Expected Result                                  | Status |
| --- | --------------------------------- | ------------------------------------------------ | ------ |
| 1   | Log in as `member1` / `Test@1234` | Lands on `/?page=dashboard`                      | [ ]    |
| 2   | Check **E-Wallet Balance** card   | Shows current balance                            | [ ]    |
| 3   | Check **Pairing Earnings** card   | Shows ₱2,000.00 (1 pair from member3 joining)    | [ ]    |
| 4   | Check **Direct Referral** card    | Shows ₱0.00 (member1 has no direct recruits yet) | [ ]    |
| 5   | Check **Indirect Referral** card  | Shows ₱0.00                                      | [ ]    |

---

### TC-028 · Pairing Cap Widget

| #   | Step                                                      | Expected Result                                                  | Status |
| --- | --------------------------------------------------------- | ---------------------------------------------------------------- | ------ |
| 1   | On the dashboard, find the **"Today's Pairing Cap"** card | —                                                                | [ ]    |
| 2   | Check the cap bar                                         | Partially filled showing 1 pair earned today                     | [ ]    |
| 3   | Check the labels                                          | Shows **"1 pairs earned today"** and **"2 remaining"** (cap = 3) | [ ]    |
| 4   | Check the **"Earned today"** row                          | Shows ₱2,000.00                                                  | [ ]    |
| 5   | Check the **"Resets at midnight"** badge                  | Visible in top right of card                                     | [ ]    |

---

### TC-029 · Binary Leg Counters

| #   | Step                               | Expected Result                             | Status |
| --- | ---------------------------------- | ------------------------------------------- | ------ |
| 1   | Find the **"🌳 Binary Legs"** card | —                                           | [ ]    |
| 2   | Check **Left Leg** count           | Shows **2** (member2 + member4 are in left) | [ ]    |
| 3   | Check **Right Leg** count          | Shows **1** (member3 is in right)           | [ ]    |
| 4   | Check **Lifetime pairs paid**      | Shows **1**                                 | [ ]    |
| 5   | Check **Pairs flushed (lifetime)** | Shows **0**                                 | [ ]    |
| 6   | Click **View Tree →** link         | Navigates to genealogy page                 | [ ]    |

---

### TC-030 · Recent Activity Feed

| #   | Step                                            | Expected Result               | Status |
| --- | ----------------------------------------------- | ----------------------------- | ------ |
| 1   | On the dashboard, scroll to **Recent Activity** | —                             | [ ]    |
| 2   | Check the first activity item                   | Shows 🤝 Pairing Bonus entry  | [ ]    |
| 3   | Check the amount                                | Shows **+₱2,000.00** in green | [ ]    |
| 4   | Click **View all →**                            | Navigates to Earnings page    | [ ]    |

---

## SECTION 6 — EARNINGS

### TC-031 · Earnings Page — All Tab

| #   | Step                                      | Expected Result                                                   | Status |
| --- | ----------------------------------------- | ----------------------------------------------------------------- | ------ |
| 1   | Click **💰 Earnings** in the sidebar      | Loads `/?page=earnings`                                           | [ ]    |
| 2   | Check the 4 summary cards at the top      | Total Earned, Pairing Bonuses, Direct Referral, Indirect Referral | [ ]    |
| 3   | Check the table has entries               | At least 1 pairing commission row                                 | [ ]    |
| 4   | Check the columns                         | Date, Type, Description, From, Amount, Status                     | [ ]    |
| 5   | Check the status badge on the pairing row | Shows **"Credited"** in green                                     | [ ]    |

---

### TC-032 · Earnings — Tab Filtering

| #   | Step                          | Expected Result                                        | Status |
| --- | ----------------------------- | ------------------------------------------------------ | ------ |
| 1   | Click the **🤝 Pairing** tab  | Table shows only pairing type rows                     | [ ]    |
| 2   | Click the **👥 Direct** tab   | Table shows only direct referral rows (may be empty)   | [ ]    |
| 3   | Click the **🔗 Indirect** tab | Table shows only indirect referral rows (may be empty) | [ ]    |
| 4   | Click **All** tab             | All commission types visible                           | [ ]    |

---

## SECTION 7 — GENEALOGY

### TC-033 · Binary Tree View

| #   | Step                                    | Expected Result                                         | Status |
| --- | --------------------------------------- | ------------------------------------------------------- | ------ |
| 1   | Click **🌳 Binary Tree** in the sidebar | Loads `/?page=genealogy&view=binary`                    | [ ]    |
| 2   | Wait for the tree to load               | Loading spinner disappears, tree nodes appear on canvas | [ ]    |
| 3   | Check the root node                     | Shows `member1` in green (active)                       | [ ]    |
| 4   | Check left child of member1             | Shows `member2` in green                                | [ ]    |
| 5   | Check right child of member1            | Shows `member3` in green                                | [ ]    |
| 6   | Check left child of member2             | Shows `member4` in green                                | [ ]    |
| 7   | Check right child of member2            | Shows `member5` in green                                | [ ]    |
| 8   | Check empty slots                       | Dashed circles shown for member3's children (none yet)  | [ ]    |
| 9   | Check the L: R: counts below each node  | Correct left/right counts shown                         | [ ]    |

---

### TC-034 · Binary Tree — Hover Tooltip

| #   | Step                                     | Expected Result                                                      | Status |
| --- | ---------------------------------------- | -------------------------------------------------------------------- | ------ |
| 1   | Hover your mouse over the `member1` node | Dark tooltip appears                                                 | [ ]    |
| 2   | Check tooltip content                    | Shows: username, package, join date, left count, right count, status | [ ]    |
| 3   | Move mouse away                          | Tooltip disappears                                                   | [ ]    |

---

### TC-035 · Binary Tree — Zoom Controls

| #   | Step                                  | Expected Result                            | Status |
| --- | ------------------------------------- | ------------------------------------------ | ------ |
| 1   | Click the **+** button above the tree | Tree zooms in                              | [ ]    |
| 2   | Click the **−** button                | Tree zooms out                             | [ ]    |
| 3   | Click **⟳ Reset**                     | Tree returns to original zoom and position | [ ]    |

---

### TC-036 · Referral Network View

| #   | Step                                         | Expected Result                                      | Status |
| --- | -------------------------------------------- | ---------------------------------------------------- | ------ |
| 1   | Click **👥 Referral Network** in the sidebar | Loads `/?page=genealogy&view=referral`               | [ ]    |
| 2   | Check the page                               | Shows "You haven't referred anyone yet." for member1 | [ ]    |

> **Note:** member1 has no direct recruits since admin was the sponsor for all members in our test tree. This is correct behaviour.

---

### TC-037 · Referral Network — Admin View

| #   | Step                                                                | Expected Result                                                | Status |
| --- | ------------------------------------------------------------------- | -------------------------------------------------------------- | ------ |
| 1   | Log out and log in as `admin`                                       | —                                                              | [ ]    |
| 2   | Click **👥** in the sidebar (Member View first: `/?page=dashboard`) | —                                                              | [ ]    |
| 3   | Click **👥 Referral Network**                                       | Loads referral genealogy                                       | [ ]    |
| 4   | Check Level 1                                                       | Shows all members sponsored by admin (member1 through member5) | [ ]    |
| 5   | Check the member count badge                                        | Shows correct number                                           | [ ]    |
| 6   | Click on a Level header                                             | Level collapses (hides members)                                | [ ]    |
| 7   | Click it again                                                      | Level expands again                                            | [ ]    |

---

## SECTION 8 — PROFILE

### TC-038 · Profile Page Display

> Log in as `member1` for this section.

| #   | Step                                           | Expected Result                                               | Status |
| --- | ---------------------------------------------- | ------------------------------------------------------------- | ------ |
| 1   | Click **⚙️ Profile & Settings** in the sidebar | Loads `/?page=profile`                                        | [ ]    |
| 2   | Check the **Account Info** card                | Shows: Username, Package, Sponsor, Binary Upline, Joined date | [ ]    |
| 3   | Check Sponsor row                              | Shows `@admin`                                                | [ ]    |
| 4   | Check Binary Upline row                        | Shows `@admin (left)`                                         | [ ]    |
| 5   | Check all form fields                          | Full Name, Email, Mobile, Address, GCash Number are editable  | [ ]    |

---

### TC-039 · Update Profile

| #   | Step                                          | Expected Result                            | Status |
| --- | --------------------------------------------- | ------------------------------------------ | ------ |
| 1   | Fill **Full Name**: `Test Member One`         | —                                          | [ ]    |
| 2   | Fill **Email**: `member1@test.com`            | —                                          | [ ]    |
| 3   | Fill **Mobile**: `09171234567`                | —                                          | [ ]    |
| 4   | Fill **GCash Number**: `09171234567`          | —                                          | [ ]    |
| 5   | Fill **Address**: `Test Address, Philippines` | —                                          | [ ]    |
| 6   | Click **💾 Save Changes**                     | Flash: **"Profile updated successfully."** | [ ]    |
| 7   | Refresh the page                              | All fields retain the saved values         | [ ]    |

---

### TC-040 · Change Password

| #   | Step                                                         | Expected Result                            | Status |
| --- | ------------------------------------------------------------ | ------------------------------------------ | ------ |
| 1   | On the profile page, enter **Current Password**: `Test@1234` | —                                          | [ ]    |
| 2   | Enter **New Password**: `NewPass@1234`                       | —                                          | [ ]    |
| 3   | Enter **Confirm New**: `NewPass@1234`                        | —                                          | [ ]    |
| 4   | Click **💾 Save Changes**                                    | Flash: **"Profile updated successfully."** | [ ]    |
| 5   | Log out                                                      | Redirects to login                         | [ ]    |
| 6   | Log in with old password `Test@1234`                         | Error: Invalid credentials                 | [ ]    |
| 7   | Log in with new password `NewPass@1234`                      | Login successful                           | [ ]    |
| 8   | Change password back to `Test@1234`                          | Saves successfully                         | [ ]    |

---

### TC-041 · Wrong Current Password

| #   | Step                                                             | Expected Result                                   | Status |
| --- | ---------------------------------------------------------------- | ------------------------------------------------- | ------ |
| 1   | On the profile page, enter **Current Password**: `wrongpassword` | —                                                 | [ ]    |
| 2   | Enter any new password                                           | —                                                 | [ ]    |
| 3   | Click **💾 Save Changes**                                        | Flash error: **"Current password is incorrect."** | [ ]    |
| 4   | Verify password was NOT changed                                  | Old password still works to log in                | [ ]    |

---

### TC-042 · Profile Photo Upload

| #   | Step                                           | Expected Result                                | Status |
| --- | ---------------------------------------------- | ---------------------------------------------- | ------ |
| 1   | On the profile page, click **📷 Change Photo** | File picker opens                              | [ ]    |
| 2   | Select any JPEG or PNG image under 2MB         | Preview updates instantly in the avatar circle | [ ]    |
| 3   | Click **💾 Save Changes**                      | Flash: **"Profile updated successfully."**     | [ ]    |
| 4   | Refresh the page                               | Photo persists in the avatar                   | [ ]    |
| 5   | Check the sidebar bottom                       | Photo shows in the user avatar                 | [ ]    |
| 6   | Check the topbar                               | Photo shows in the top-right avatar            | [ ]    |

---

## SECTION 9 — PAYOUTS (MEMBER SIDE)

> member1 must have a balance. If not, ensure member3 registered under member1 to trigger a pairing bonus.

### TC-043 · Payout Page Display

| #   | Step                                | Expected Result                                 | Status |
| --- | ----------------------------------- | ----------------------------------------------- | ------ |
| 1   | Click **💳 Payouts** in the sidebar | Loads `/?page=payout`                           | [ ]    |
| 2   | Check the blue balance card         | Shows current e-wallet balance                  | [ ]    |
| 3   | Check minimum payout shown          | **"Minimum withdrawal: ₱500.00"**               | [ ]    |
| 4   | Check the payout request form       | Amount field, GCash number field, Submit button | [ ]    |
| 5   | Check GCash field                   | Pre-filled with the number saved in profile     | [ ]    |

---

### TC-044 · Payout Validation — Amount Below Minimum

| #   | Step                           | Expected Result                               | Status |
| --- | ------------------------------ | --------------------------------------------- | ------ |
| 1   | Type `100` in the Amount field | Hint: **"Minimum is ₱500.00"** in red         | [ ]    |
| 2   | Try submitting                 | Server-side check: error about minimum payout | [ ]    |

---

### TC-045 · Payout Validation — Amount Exceeds Balance

| #   | Step                                                    | Expected Result                                  | Status |
| --- | ------------------------------------------------------- | ------------------------------------------------ | ------ |
| 1   | Type an amount larger than your balance (e.g. `999999`) | Hint: **"Exceeds your balance of ₱X.XX"** in red | [ ]    |
| 2   | Try submitting                                          | Error: Insufficient balance                      | [ ]    |

---

### TC-046 · Submit a Payout Request

| #   | Step                                              | Expected Result                                                        | Status |
| --- | ------------------------------------------------- | ---------------------------------------------------------------------- | ------ |
| 1   | Enter a valid amount (e.g. `500`) in Amount field | Hint shows max balance                                                 | [ ]    |
| 2   | Ensure GCash number is filled                     | —                                                                      | [ ]    |
| 3   | Click **Submit Payout Request**                   | Flash: **"Payout request submitted. Admin will process it shortly."**  | [ ]    |
| 4   | Check the payout history table                    | New row appears with status **"Pending"** in yellow                    | [ ]    |
| 5   | Check the request form                            | Replaced with message: **"You already have a pending payout request"** | [ ]    |

---

### TC-047 · Duplicate Payout Request Prevention

| #   | Step                                                  | Expected Result                                         | Status |
| --- | ----------------------------------------------------- | ------------------------------------------------------- | ------ |
| 1   | While a pending request exists, try to submit another | Error: **"You already have a pending payout request."** | [ ]    |

---

## SECTION 10 — ADMIN: MEMBER MANAGEMENT

> Log in as `admin`.

### TC-048 · Members List Page

| #   | Step                                | Expected Result                                                   | Status |
| --- | ----------------------------------- | ----------------------------------------------------------------- | ------ |
| 1   | Click **👥 Members** in the sidebar | Loads `/?page=admin_users`                                        | [ ]    |
| 2   | Check the 4 stat cards              | Total, Active, Suspended, Joined Today                            | [ ]    |
| 3   | Check the table                     | member1 through member5 are listed                                | [ ]    |
| 4   | Check each row                      | Username, Full Name, Package, Balance, Pairs Paid, Joined, Status | [ ]    |

---

### TC-049 · Search Members

| #   | Step                                                  | Expected Result                      | Status |
| --- | ----------------------------------------------------- | ------------------------------------ | ------ |
| 1   | Type `member1` in the search box and click **Search** | Table shows only `member1`           | [ ]    |
| 2   | Type `Test Member` in search                          | Shows member1 (matched by full name) | [ ]    |
| 3   | Clear search box and search again                     | All members visible                  | [ ]    |

---

### TC-050 · Filter by Status

| #   | Step                                       | Expected Result               | Status |
| --- | ------------------------------------------ | ----------------------------- | ------ |
| 1   | Select **Active** from the Status dropdown | Only active members shown     | [ ]    |
| 2   | Select **Suspended**                       | Message: no suspended members | [ ]    |
| 3   | Select **All Statuses**                    | All members shown             | [ ]    |

---

### TC-051 · View Member Detail

| #   | Step                                | Expected Result                                                 | Status |
| --- | ----------------------------------- | --------------------------------------------------------------- | ------ |
| 1   | Click **View** next to `member1`    | Loads `/?page=admin_user_view&id=X`                             | [ ]    |
| 2   | Check KPI cards at top              | E-Wallet Balance, Total Earned, Pairs Paid/Today, Pairs Flushed | [ ]    |
| 3   | Check the **Profile** card          | Full Name: Test Member One, Email, Mobile, GCash shown          | [ ]    |
| 4   | Check the **Binary Placement** card | Sponsor: @admin, Upline: @admin (left), Left count, Right count | [ ]    |
| 5   | Check the leg bar                   | Visual left/right leg counters                                  | [ ]    |
| 6   | Click **💰 Commissions** tab        | Shows pairing commission entry                                  | [ ]    |
| 7   | Click **📒 E-Wallet Ledger** tab    | Shows credit entries with balance_after column                  | [ ]    |
| 8   | Click **💳 Payouts** tab            | Shows pending payout request                                    | [ ]    |

---

### TC-052 · Suspend and Activate a Member

| #   | Step                                                     | Expected Result                       | Status |
| --- | -------------------------------------------------------- | ------------------------------------- | ------ |
| 1   | On the Members list, click **Suspend** next to `member5` | Confirmation modal appears            | [ ]    |
| 2   | Check modal title                                        | **"Suspend Member"**                  | [ ]    |
| 3   | Check modal message                                      | Mentions @member5 and loss of access  | [ ]    |
| 4   | Click **Suspend** in the modal                           | Modal closes                          | [ ]    |
| 5   | Check member5's status badge                             | Now shows **"Suspended"** in red      | [ ]    |
| 6   | Try logging in as `member5` in another tab               | Error or redirect blocked             | [ ]    |
| 7   | Back in admin, click **Activate** next to `member5`      | Confirmation modal with Activate text | [ ]    |
| 8   | Click **Activate**                                       | Status changes back to **"Active"**   | [ ]    |
| 9   | Log in as `member5` again                                | Login succeeds                        | [ ]    |

---

## SECTION 11 — ADMIN: PAYOUT MANAGEMENT

### TC-053 · View Pending Payouts

| #   | Step                                   | Expected Result                                            | Status |
| --- | -------------------------------------- | ---------------------------------------------------------- | ------ |
| 1   | Click **💸 Payouts** in the sidebar    | Loads `/?page=admin_payouts&status=pending`                | [ ]    |
| 2   | Check the **⏳ Pending** tab is active | —                                                          | [ ]    |
| 3   | Check the payout row for member1       | Shows: username, amount ₱500, GCash number, requested date | [ ]    |
| 4   | Check the GCash **📋 copy button**     | Click it — browser shows "Copied" toast                    | [ ]    |

---

### TC-054 · Approve a Payout

| #   | Step                                                  | Expected Result                                                             | Status |
| --- | ----------------------------------------------------- | --------------------------------------------------------------------------- | ------ |
| 1   | Click **✓ Approve** next to member1's request         | Modal opens                                                                 | [ ]    |
| 2   | Check modal title                                     | **"✓ Approve Payout"**                                                      | [ ]    |
| 3   | Check modal description                               | Mentions amount and that you need to send via GCash before marking complete | [ ]    |
| 4   | Leave note blank, click **✓ Approve Payout** in modal | Modal closes                                                                | [ ]    |
| 5   | Check flash message                                   | Green: **"Payout approved."**                                               | [ ]    |
| 6   | Click the **✅ Approved** tab                         | member1's request is now shown here                                         | [ ]    |
| 7   | Check the action column                               | Shows GCash instruction box + **"✅ Mark Complete"** button                 | [ ]    |

---

### TC-055 · Mark Payout as Complete

**What we are testing:** Completing a payout deducts the balance from member's e-wallet.

| #   | Step                                                                 | Expected Result                                             | Status |
| --- | -------------------------------------------------------------------- | ----------------------------------------------------------- | ------ |
| 1   | Note member1's current balance (check `/?page=admin_user_view&id=X`) | Record: ₱**\_\_**                                           | [ ]    |
| 2   | Back on Payouts, click **✅ Mark Complete**                          | Modal opens                                                 | [ ]    |
| 3   | Check modal                                                          | Says you've sent ₱500 to member1 via GCash                  | [ ]    |
| 4   | Add note: `Sent via GCash`                                           | —                                                           | [ ]    |
| 5   | Click **✅ Mark as Completed**                                       | Flash: **"Payout marked as completed. E-wallet deducted."** | [ ]    |
| 6   | Click **💚 Completed** tab                                           | member1's payout now shows here                             | [ ]    |
| 7   | Go to member1's user view                                            | Balance is now ₱500 less than before                        | [ ]    |
| 8   | Log in as member1, go to Payouts                                     | Request shows **"Completed"** status                        | [ ]    |
| 9   | Check member1's topbar balance                                       | Reflects the deduction                                      | [ ]    |

---

### TC-056 · Reject a Payout

| #   | Step                                                           | Expected Result                               | Status |
| --- | -------------------------------------------------------------- | --------------------------------------------- | ------ |
| 1   | Submit another payout request as `member1` (if balance allows) | Request created                               | [ ]    |
| 2   | In admin, click **✕ Reject** on the new request                | Modal opens                                   | [ ]    |
| 3   | Enter reason: `Test rejection`                                 | —                                             | [ ]    |
| 4   | Click **✕ Reject Payout**                                      | Flash: **"Payout rejected."**                 | [ ]    |
| 5   | Check **❌ Rejected** tab                                      | Request appears here with the reason          | [ ]    |
| 6   | Check member1's e-wallet balance                               | Unchanged — balance NOT deducted on rejection | [ ]    |
| 7   | Log in as member1, go to Payouts                               | Shows **"Rejected"** with the admin's note    | [ ]    |

---

## SECTION 12 — ADMIN: SETTINGS

### TC-057 · Settings Page

| #   | Step                                   | Expected Result                                                 | Status |
| --- | -------------------------------------- | --------------------------------------------------------------- | ------ |
| 1   | Click **⚙️ Settings** in the sidebar   | Loads `/?page=admin_settings`                                   | [ ]    |
| 2   | Check the **General Settings** form    | Site Name, Tagline, Contact Email, Min Payout, Maintenance Mode | [ ]    |
| 3   | Check **Daily Pair Cap Reset** section | Shows last reset timestamp (or "Never run")                     | [ ]    |
| 4   | Check the crontab reference            | Shows `0 0 * * * php /path/to/mlm/cron/midnight_reset.php`      | [ ]    |
| 5   | Check **System Info** card             | PHP version, MySQL version, Server Time, App URL, Environment   | [ ]    |

---

### TC-058 · Update Site Settings

| #   | Step                                                 | Expected Result                                   | Status |
| --- | ---------------------------------------------------- | ------------------------------------------------- | ------ |
| 1   | Change **Site Name** to `Kensue`                     | —                                                 | [ ]    |
| 2   | Change **Minimum Payout** to `1000`                  | —                                                 | [ ]    |
| 3   | Click **💾 Save Settings**                           | Flash: **"Settings saved."**                      | [ ]    |
| 4   | Refresh the page                                     | Site Name shows `Kensue`, Min Payout shows `1000` | [ ]    |
| 5   | Go to login page                                     | Page title now shows **"Login — Kensue"**         | [ ]    |
| 6   | Revert site name to `Kensue` and min payout to `500` | Flash: **"Settings saved."**                      | [ ]    |

---

### TC-059 · Maintenance Mode

| #   | Step                                                          | Expected Result                                                   | Status |
| --- | ------------------------------------------------------------- | ----------------------------------------------------------------- | ------ |
| 1   | Set Maintenance Mode to **On** and save                       | Flash: **"Settings saved."**                                      | [ ]    |
| 2   | Open an incognito window and go to `http://localhost/kensue/` | Shows maintenance page: "We're performing scheduled maintenance." | [ ]    |
| 3   | Try `/?page=login` in incognito                               | Still shows maintenance page                                      | [ ]    |
| 4   | In admin, set Maintenance Mode back to **Off**                | —                                                                 | [ ]    |
| 5   | Refresh the incognito window                                  | Login page returns                                                | [ ]    |

---

### TC-060 · Manual Daily Reset

| #   | Step                                                     | Expected Result                                        | Status |
| --- | -------------------------------------------------------- | ------------------------------------------------------ | ------ |
| 1   | Note member1's `pairs_paid_today` value (from user view) | Record: **\_\_**                                       | [ ]    |
| 2   | On settings page, click **⟳ Run Daily Reset Now**        | Confirmation dialog appears                            | [ ]    |
| 3   | Click **OK**                                             | Flash: **"Daily pair counter reset for X member(s)."** | [ ]    |
| 4   | Check **Last Reset** timestamp                           | Shows the current time                                 | [ ]    |
| 5   | Go to member1's user view                                | `pairs_paid_today` is now `0`                          | [ ]    |
| 6   | Log in as member1 and check dashboard                    | **"0 pairs earned today"** and **"3 remaining"**       | [ ]    |

---

## SECTION 13 — REAL-TIME COMMISSION VERIFICATION

This is the most important section — it verifies the core MLM calculation engine.

### TC-061 · Pairing Bonus Fires Instantly on Registration

**Setup:** Ensure member1 has 0 pairs today (run manual reset first from TC-060).

| #   | Step                                                                                                                   | Expected Result                                                                              | Status |
| --- | ---------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- | ------ |
| 1   | Log in as `member1` in one tab                                                                                         | Note current balance: ₱**\_\_**                                                              | [ ]    |
| 2   | In a separate incognito window, register a new member (`member6`) using a fresh code, with `member3` as upline on Left | —                                                                                            | [ ]    |
| 3   | Immediately go back to member1's dashboard tab and refresh                                                             | —                                                                                            | [ ]    |
| 4   | Check if balance changed                                                                                               | member1 should NOT get a pair yet (member3 now has left but no right)                        | [ ]    |
| 5   | Register another new member (`member7`) with `member3` as upline on Right                                              | —                                                                                            | [ ]    |
| 6   | Refresh member1's dashboard                                                                                            | member1's balance increased by ₱2,000 (pair formed under member3 → propagates up to member1) | [ ]    |
| 7   | Check the pairing cap widget                                                                                           | +1 pair earned today shown                                                                   | [ ]    |

---

### TC-062 · Direct Referral Fires Instantly

| #   | Step                                          | Expected Result                                 | Status |
| --- | --------------------------------------------- | ----------------------------------------------- | ------ |
| 1   | Note admin's e-wallet balance                 | ₱**\_\_**                                       | [ ]    |
| 2   | Register a new member with `admin` as sponsor | —                                               | [ ]    |
| 3   | Immediately check admin's balance             | Increased by ₱500.00 (direct referral bonus)    | [ ]    |
| 4   | Check admin's Earnings page                   | New **"Direct Referral"** entry timestamped now | [ ]    |

---

### TC-063 · Indirect Referral Fires Instantly

| #   | Step                                                        | Expected Result                                                  | Status |
| --- | ----------------------------------------------------------- | ---------------------------------------------------------------- | ------ |
| 1   | Check member1's balance before                              | ₱**\_\_**                                                        | [ ]    |
| 2   | Register a new member with `member1` as sponsor             | —                                                                | [ ]    |
| 3   | Check admin's earnings (admin is Level 1 upline of member1) | New **"Indirect Referral — Level 1"** entry                      | [ ]    |
| 4   | Check member1's earnings                                    | New **"Direct Referral"** entry since they're the direct sponsor | [ ]    |

---

### TC-064 · Daily Cap Flush-Out

**Setup:** Ensure member1 has used 2 of 3 daily pairs (pairs_paid_today = 2).

| #   | Step                                                                | Expected Result                                              | Status |
| --- | ------------------------------------------------------------------- | ------------------------------------------------------------ | ------ |
| 1   | Register members to trigger exactly 2 pairs for member1 today       | pairs_paid_today = 2                                         | [ ]    |
| 2   | Register one more pair-forming member (triggers 1 pair for member1) | Balance increases by ₱2,000 — this uses up the last cap slot | [ ]    |
| 3   | Check the cap widget                                                | Shows **"3 pairs earned today"** — bar is full red           | [ ]    |
| 4   | Check for **"Daily cap reached"** warning                           | Orange warning text visible on cap widget                    | [ ]    |
| 5   | Register yet another pair-forming member                            | —                                                            | [ ]    |
| 6   | Check member1's balance                                             | Did NOT increase — pair was flushed                          | [ ]    |
| 7   | Check member1's Earnings page                                       | New **"Flushed"** entry with yellow badge                    | [ ]    |
| 8   | Run Manual Reset from admin settings                                | pairs_paid_today resets to 0                                 | [ ]    |
| 9   | Trigger another pair for member1                                    | Balance increases again — cap refreshed                      | [ ]    |

---

## SECTION 14 — SECURITY TESTS

### TC-065 · Direct PHP File Access Blocked

| #   | Step                                                            | Expected Result   | Status |
| --- | --------------------------------------------------------------- | ----------------- | ------ |
| 1   | Visit `http://localhost/kensue/config/db.php`                   | **403 Forbidden** | [ ]    |
| 2   | Visit `http://localhost/kensue/core/Auth.php`                   | **403 Forbidden** | [ ]    |
| 3   | Visit `http://localhost/kensue/models/User.php`                 | **403 Forbidden** | [ ]    |
| 4   | Visit `http://localhost/kensue/controllers/AdminController.php` | **403 Forbidden** | [ ]    |
| 5   | Visit `http://localhost/kensue/cron/midnight_reset.php`         | **403 Forbidden** | [ ]    |

---

### TC-066 · Unauthorised Admin Access Blocked

| #   | Step                                                    | Expected Result                 | Status |
| --- | ------------------------------------------------------- | ------------------------------- | ------ |
| 1   | Log out completely                                      | —                               | [ ]    |
| 2   | Visit `/?page=admin` directly                           | Redirects to login              | [ ]    |
| 3   | Log in as `member1` (non-admin)                         | Lands on member dashboard       | [ ]    |
| 4   | Visit `/?page=admin` directly while logged in as member | Redirects to `/?page=dashboard` | [ ]    |
| 5   | Visit `/?page=admin_users`                              | Redirects to `/?page=dashboard` | [ ]    |
| 6   | Visit `/?page=admin_payouts`                            | Redirects to `/?page=dashboard` | [ ]    |

---

### TC-067 · CSRF Protection

| #   | Step                                                                              | Expected Result                                         | Status |
| --- | --------------------------------------------------------------------------------- | ------------------------------------------------------- | ------ |
| 1   | Open browser DevTools (F12) → Network tab                                         | —                                                       | [ ]    |
| 2   | Submit the login form normally                                                    | Check the POST request — it includes `csrf_token` field | [ ]    |
| 3   | Try submitting a POST to `/?page=do_login` from a different tab without the token | Returns **403 Forbidden**                               | [ ]    |

---

### TC-068 · Guest Cannot Access Member Pages

| #   | Step                     | Expected Result                                                   | Status |
| --- | ------------------------ | ----------------------------------------------------------------- | ------ |
| 1   | Log out                  | —                                                                 | [ ]    |
| 2   | Visit `/?page=dashboard` | Redirects to login with message: **"Please log in to continue."** | [ ]    |
| 3   | Visit `/?page=earnings`  | Redirects to login                                                | [ ]    |
| 4   | Visit `/?page=payout`    | Redirects to login                                                | [ ]    |
| 5   | Visit `/?page=genealogy` | Redirects to login                                                | [ ]    |
| 6   | Visit `/?page=profile`   | Redirects to login                                                | [ ]    |

---

## SECTION 15 — MOBILE RESPONSIVENESS

### TC-069 · Mobile Sidebar Behaviour

Open browser DevTools (F12) → Click the mobile/tablet icon to toggle device mode. Set to **375px width** (iPhone).

| #   | Step                                          | Expected Result                                | Status |
| --- | --------------------------------------------- | ---------------------------------------------- | ------ |
| 1   | Log in and go to dashboard                    | Page renders without horizontal scrollbar      | [ ]    |
| 2   | Check sidebar                                 | Sidebar is **hidden** by default on mobile     | [ ]    |
| 3   | Check topbar                                  | Hamburger ☰ button is visible                 | [ ]    |
| 4   | Click the hamburger ☰                        | Sidebar slides in from the left                | [ ]    |
| 5   | Check overlay                                 | Dark overlay covers the content behind sidebar | [ ]    |
| 6   | Click the dark overlay                        | Sidebar slides back out                        | [ ]    |
| 7   | Open sidebar again, then press **Escape** key | Sidebar closes                                 | [ ]    |

---

### TC-070 · Swipe Gesture

(Test on a real mobile device or using touch simulation in DevTools)

| #   | Step                                                    | Expected Result | Status |
| --- | ------------------------------------------------------- | --------------- | ------ |
| 1   | On mobile, swipe right from the left edge of the screen | Sidebar opens   | [ ]    |
| 2   | Swipe left anywhere on the page                         | Sidebar closes  | [ ]    |

---

### TC-071 · Mobile Table Scrolling

| #   | Step                                     | Expected Result                                 | Status |
| --- | ---------------------------------------- | ----------------------------------------------- | ------ |
| 1   | Go to Earnings page on mobile width      | Table is scrollable horizontally                | [ ]    |
| 2   | Go to Admin Members list on mobile width | Table scrolls horizontally, page does not break | [ ]    |
| 3   | Go to Admin Payouts on mobile width      | Table scrolls, action buttons visible           | [ ]    |

---

### TC-072 · Mobile Grid Layout

| #   | Step                          | Expected Result                         | Status |
| --- | ----------------------------- | --------------------------------------- | ------ |
| 1   | On dashboard at 375px         | 4 KPI cards stack into 2×2 grid         | [ ]    |
| 2   | On dashboard at 640px or less | All cards become full-width (1 column)  | [ ]    |
| 3   | On registration page at 375px | Step 2 form rows stack vertically       | [ ]    |
| 4   | On profile page at 375px      | Two-column layout becomes single column | [ ]    |

---

## SECTION 16 — ADMIN DASHBOARD OVERVIEW

### TC-073 · Dashboard KPI Accuracy

| #   | Step                                     | Expected Result                     | Status |
| --- | ---------------------------------------- | ----------------------------------- | ------ |
| 1   | Log in as admin and go to `/?page=admin` | —                                   | [ ]    |
| 2   | Check **Total Members** card             | Count matches actual members in DB  | [ ]    |
| 3   | Check **"+ X today"** sub-text           | Shows how many joined today         | [ ]    |
| 4   | Check **Code Revenue**                   | Sum of all used code prices         | [ ]    |
| 5   | Check **Pending Payouts**                | Matches pending payout total amount | [ ]    |
| 6   | Check **Total Paid Out**                 | Matches sum of completed payouts    | [ ]    |
| 7   | Check **Total Commissions Paid**         | Sum of all credited commissions     | [ ]    |
| 8   | Check **Total E-Wallet Holdings**        | Sum of all member balances          | [ ]    |
| 9   | Check **Suspended Members** count        | Shows `0` (all activated)           | [ ]    |

---

### TC-074 · Recent Members Feed

| #   | Step                                              | Expected Result                              | Status |
| --- | ------------------------------------------------- | -------------------------------------------- | ------ |
| 1   | On admin dashboard, check **Recent Members** card | Shows last 6 members                         | [ ]    |
| 2   | Most recent member is at the top                  | Ordered by join date descending              | [ ]    |
| 3   | Each row shows                                    | Avatar initial, username, package, join time | [ ]    |
| 4   | Click **View** next to any member                 | Opens member detail page                     | [ ]    |
| 5   | Click **View all →**                              | Goes to Members list                         | [ ]    |

---

## SECTION 17 — EDGE CASES

### TC-075 · Admin Profile Has No Sponsor/Upline

| #   | Step                             | Expected Result                   | Status |
| --- | -------------------------------- | --------------------------------- | ------ |
| 1   | Log in as admin                  | —                                 | [ ]    |
| 2   | Click **Member View** in sidebar | Goes to `/?page=dashboard`        | [ ]    |
| 3   | Click **Profile & Settings**     | Profile page loads without errors | [ ]    |
| 4   | Check Sponsor row                | Shows **—** (dash, not an error)  | [ ]    |
| 5   | Check Binary Upline row          | Shows **—** (dash, not an error)  | [ ]    |

---

### TC-076 · Zero Balance Payout Attempt

| #   | Step                               | Expected Result                                                                       | Status |
| --- | ---------------------------------- | ------------------------------------------------------------------------------------- | ------ |
| 1   | Log in as a member with ₱0 balance | —                                                                                     | [ ]    |
| 2   | Go to Payouts page                 | —                                                                                     | [ ]    |
| 3   | Check the request form area        | Shows message: **"Minimum payout is ₱500.00. Your current balance is insufficient."** | [ ]    |
| 4   | Verify the form is hidden          | No amount field or submit button shown                                                | [ ]    |

---

### TC-077 · Invalid Registration Code Cannot Be Reused

| #   | Step                                                         | Expected Result                                    | Status |
| --- | ------------------------------------------------------------ | -------------------------------------------------- | ------ |
| 1   | Note a code that was already used (status = "used" in admin) | Record: \***\*\_\_\_\_\*\***                       | [ ]    |
| 2   | Start registration and enter the used code                   | —                                                  | [ ]    |
| 3   | Click **Validate**                                           | Red hint: **"Code is invalid, used, or expired."** | [ ]    |
| 4   | Continue button stays disabled                               | Cannot proceed with a used code                    | [ ]    |

---

### TC-078 · Binary Tree Renders for Members with No Downlines

| #   | Step                               | Expected Result                                     | Status |
| --- | ---------------------------------- | --------------------------------------------------- | ------ |
| 1   | Log in as `member5`                | —                                                   | [ ]    |
| 2   | Go to Binary Tree                  | Tree loads                                          | [ ]    |
| 3   | Check the canvas                   | member5 node shown with 2 empty dashed slot circles | [ ]    |
| 4   | Check left/right counts below node | Shows `L:0 R:0`                                     | [ ]    |

---

## FINAL QA CHECKLIST

Use this summary to confirm all critical paths pass before going live.

| #   | Critical Function                                      | Pass? |
| --- | ------------------------------------------------------ | ----- |
| 1   | Admin can log in                                       | [ ]   |
| 2   | Member can register with a valid code                  | [ ]   |
| 3   | Binary slot checking prevents double placement         | [ ]   |
| 4   | Pairing bonus fires instantly on registration          | [ ]   |
| 5   | Daily cap limits pairing bonuses correctly             | [ ]   |
| 6   | Flushed pairs show in earnings as "Flushed"            | [ ]   |
| 7   | Direct referral bonus fires instantly                  | [ ]   |
| 8   | Indirect referral fires up 10 levels                   | [ ]   |
| 9   | Manual midnight reset resets pairs_paid_today          | [ ]   |
| 10  | Member can view binary tree                            | [ ]   |
| 11  | Member can view referral genealogy                     | [ ]   |
| 12  | Member can update profile and password                 | [ ]   |
| 13  | Payout request workflow (request → approve → complete) | [ ]   |
| 14  | E-wallet deducted only on payout completion            | [ ]   |
| 15  | Admin can generate and export reg codes                | [ ]   |
| 16  | Package CRUD with 10 indirect levels works             | [ ]   |
| 17  | Suspend/activate member works                          | [ ]   |
| 18  | Direct PHP file access blocked (403)                   | [ ]   |
| 19  | Non-admin cannot access admin pages                    | [ ]   |
| 20  | Mobile sidebar and responsiveness                      | [ ]   |

---

## BUG REPORT TEMPLATE

When you find a failure, fill in one of these for each bug:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
BUG REPORT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Test Case:     TC-XXX
Severity:      Critical / High / Medium / Low
Date Found:
Found By:

Steps to Reproduce:
1.
2.
3.

Expected Result:

Actual Result:

Screenshot/Error Message:


Status:        Open / Fixed / Verified
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Severity Guide:**

- **Critical** — System crashes, data is wrong, money calculations are off
- **High** — Feature doesn't work at all, security hole
- **Medium** — Feature partially works, visual layout broken
- **Low** — Minor text, colour, or alignment issue

---

_QA Testing Guide — Kensue Binary System v1.0_
_Total Test Cases: 78 across 17 sections_
