# Beitragsanalyse – Plugin for Admidio

**Version:** 1.0.0  
**Date:** April 2026  
**Author:** Pascal Christmann  
**License:** GNU General Public License v2.0  
**Minimum Admidio version:** 5.1.0  

---

## Table of Contents

1. [Overview](#1-overview)
2. [Requirements](#2-requirements)
3. [Setting up the Admidio data structure](#3-setting-up-the-admidio-data-structure)
   - 3.1 [Create the sport group category](#31-create-the-sport-group-category)
   - 3.2 [Create roles for sport groups](#32-create-roles-for-sport-groups)
   - 3.3 [Create the family membership category](#33-create-the-family-membership-category)
   - 3.4 [Check the fee profile field](#34-check-the-fee-profile-field)
4. [Installation](#4-installation)
   - 4.1 [Copy the plugin files](#41-copy-the-plugin-files)
   - 4.2 [Activate the plugin in Plugin Manager](#42-activate-the-plugin-in-plugin-manager)
   - 4.3 [Create a menu entry](#43-create-a-menu-entry)
   - 4.4 [Restrict access on the menu entry](#44-restrict-access-on-the-menu-entry)
5. [Settings](#5-settings)
   - 5.1 [Plugin enabled](#51-plugin-enabled)
   - 5.2 [Visible to roles](#52-visible-to-roles)
   - 5.3 [Sport group category](#53-sport-group-category)
   - 5.4 [Family membership category](#54-family-membership-category)
   - 5.5 [Fee profile field](#55-fee-profile-field)
6. [Usage](#6-usage)
   - 6.1 [Summary table – fees per sport group](#61-summary-table--fees-per-sport-group)
   - 6.2 [Detail table – share per member](#62-detail-table--share-per-member)
7. [Calculation logic](#7-calculation-logic)
   - 7.1 [Individual members](#71-individual-members)
   - 7.2 [Family memberships](#72-family-memberships)
   - 7.3 [Members without sport group assignment](#73-members-without-sport-group-assignment)
   - 7.4 [Worked example](#74-worked-example)
8. [Access rights in detail](#8-access-rights-in-detail)
9. [Tips and troubleshooting](#9-tips-and-troubleshooting)
10. [Changelog](#10-changelog)

---

## 1. Overview

The **Beitragsanalyse** plugin calculates what share of the total membership fees
collected by an organisation is attributable to each individual sport group (Sparte).
It reads data directly from the Admidio database, so no prior CSV export is needed.

**Key features:**

- Proportional distribution of each member's individual fee across all sport groups
  (roles) the member actively belongs to.
- Special handling of family memberships: the family fee is first divided equally
  among all family members, then each member's share is split across their sport groups.
- Summary table showing the total allocated fee per sport group and a grand total.
- Detail table showing the exact share per member and sport group, sorted by last name.
- Role-based access control: the report is only visible to authorised members.
- Fully configurable through the plugin settings in Admidio – no code changes required.

---

## 2. Requirements

| Requirement | Minimum version |
|---|---|
| Admidio | 5.1.0 |
| PHP | 8.2 |
| Database | MySQL 5.7 / MariaDB 10.3 / PostgreSQL 11 |

The plugin uses only standard Admidio 5.1 features and has no external dependencies.

---

## 3. Setting up the Admidio data structure

Before the plugin can be used meaningfully, the appropriate categories, roles, and a
profile field must exist in Admidio. If they already exist, sections 3.1–3.4 can be
skipped.

### 3.1 Create the sport group category

1. In Admidio: **Administration → Roles → Manage role categories**
2. Create a new category, for example named **"Sport groups"**.
3. Type: **Roles** (default).
4. Save.

> **Note:** The category name is freely chosen. The plugin will later let you select
> this category – all roles inside it will then be treated as sport groups (Sparten).

### 3.2 Create roles for sport groups

1. **Administration → Roles → Create new role**
2. Create one role for each sport group, for example:
   - Archery
   - Capoeira
   - Football
   - Judo
   - Nordic Walking
   - Back school
   - Strong Nation
   - Yoga
   - Zumba
3. Assign each role to the category **"Sport groups"** (from step 3.1).
4. Assign members of the respective sport group to its role
   (**Administration → Members → Edit role membership**).

### 3.3 Create the family membership category

This step is **optional**. It is only needed when the club has family memberships
where multiple members share a single fee.

1. **Administration → Roles → Manage role categories**
2. Create a new category, for example **"Families"**.
3. Create one role per family, for example **"Family Lindner"**.
4. Assign all family members to that role.
5. Enter the family fee as a profile field value for **exactly one** family member
   (typically the primary member). All other family members should have a value of 0
   or leave the field empty.

> **Important:** The plugin automatically searches for the first family member with a
> fee value > 0 and uses that value as the shared family fee.

### 3.4 Check the fee profile field

1. **Administration → Profile settings**
2. Check whether a profile field for the membership fee exists (e.g. **"Fee"** or
   **"Annual fee"**).
3. If not: create a new field with the type **"Decimal number"** or **"Integer"**.
4. Enter the fee in euros for each member in their profile (decimal comma is accepted).

> **Tip:** The plugin accepts fees with a comma as the decimal separator
> (e.g. "84,00") and converts them internally to a numeric value.

---

## 4. Installation

### 4.1 Copy the plugin files

1. Download the plugin package or create the `beitragsanalyse` folder from the
   provided source files.
2. Copy the **entire folder** `beitragsanalyse` into the `plugins/` directory of
   your Admidio installation:

   ```
   <admidio-root>/
   └── plugins/
       └── beitragsanalyse/        ← copy here
           ├── beitragsanalyse.json
           ├── index.php
           ├── classes/
           ├── templates/
           └── languages/
   ```

3. Make sure the web server user has read permission on all files.

> **Important:** Copy the folder `beitragsanalyse` itself into `plugins/`, not just
> its contents. The folder name must be exactly `beitragsanalyse`.

### 4.2 Activate the plugin in Plugin Manager

1. Log in to Admidio as an administrator.
2. Navigate to **Administration → Plugins**.
3. The plugin **"Beitragsanalyse"** will appear in the list of available plugins.
4. Click **"Install"** or **"Activate"**.
5. The Plugin Manager will automatically set up the required database entries.

### 4.3 Create a menu entry

To make the plugin accessible through the Admidio menu, a menu entry must be created:

1. **Administration → Menu**
2. Click **"Create new menu entry"**.
3. Fill in the fields:

   | Field | Value |
   |---|---|
   | Name | e.g. "Fee analysis" |
   | URL | `{ADMIDIO_URL}/plugins/beitragsanalyse/index.php` |
   | Icon | e.g. `bi-bar-chart` or `fa-chart-bar` |
   | Parent menu | Main menu or a sub-menu of your choice |

4. Save.

### 4.4 Restrict access on the menu entry

The menu entry itself can be restricted to specific roles – this is the **first
security layer**. Members without the required role will never see the menu item.

1. Edit the newly created entry in the menu management.
2. Under **"Visible to roles"**, select the desired roles, e.g. "Board" or
   "Treasurer".
3. Save.

> **Note:** The plugin also provides a **second security layer** in the plugin
> settings (section 5.2). Both mechanisms can be combined.

---

## 5. Settings

The plugin settings are accessed via **Administration → Plugins → Beitragsanalyse →
Settings**.

### 5.1 Plugin enabled

| | |
|---|---|
| **Values** | Enabled / Disabled |
| **Default** | Enabled |

Disables the plugin entirely. When disabled, all users see only the message
*"Module disabled"*, even if they have the necessary access rights.

---

### 5.2 Visible to roles

| | |
|---|---|
| **Values** | Multiple selection from all available roles |
| **Default** | (empty = all logged-in members) |

Defines which members are allowed to open the plugin and view the report.
Only members belonging to **at least one** of the selected roles are granted access.

If no role is selected, all logged-in members can access the plugin.
Visitors who are not logged in never have access.

**Recommendation:** Select roles such as "Board" and "Treasurer" here so that the
fee analysis is visible only to authorised persons.

---

### 5.3 Sport group category

| | |
|---|---|
| **Values** | Selection from all role categories of type "ROL" |
| **Default** | (not set) |

**Required field.** Select the role category whose roles should be used as sport
groups (Sparten) for fee distribution.

All active role memberships in this category are used in the calculation. A role
without active members is ignored.

> **Without this setting, the plugin only displays a configuration warning.**

---

### 5.4 Family membership category

| | |
|---|---|
| **Values** | Selection from all role categories of type "ROL" |
| **Default** | (not set = disabled) |

**Optional field.** Select the role category whose roles represent families.

If this field is empty, all members are treated as individual members.
If a category is selected, all members who belong to a role in this category are
recognised as family members and their fee is calculated according to the family logic
(see section 7.2).

---

### 5.5 Fee profile field

| | |
|---|---|
| **Values** | Selection from profile fields of type Decimal, Integer, or Text |
| **Default** | (not set) |

**Required field.** Select the profile field that holds the membership fee as a number.

> **Note:** The field must contain a numeric value (in euros) per member.
> A comma as the decimal separator is accepted. Empty fields or values ≤ 0 are treated
> as "no fee" and are not included in the calculation.

---

## 6. Usage

Once the settings have been configured, an authorised member opens the plugin via
its menu entry. The page loads automatically and displays the current data from the
database – no further user input is required.

### 6.1 Summary table – fees per sport group

The first table summarises the distributed fees per sport group:

```
┌─────────────────────┬──────────────────┐
│ Sport group         │ Total fees (€)   │
├─────────────────────┼──────────────────┤
│ Archery             │         245.00   │
│ Capoeira            │         312.00   │
│ Football            │         876.00   │
│ Judo                │         540.00   │
│ Nordic Walking      │         198.00   │
│ Back school         │         467.00   │
│ Strong Nation       │         325.00   │
│ Yoga                │         289.00   │
│ Zumba               │         434.00   │
│ Zumba Kids          │         112.00   │
├─────────────────────┼──────────────────┤
│ No sport group      │          48.00   │
├─────────────────────┼──────────────────┤
│ Total               │       3,846.00   │
└─────────────────────┴──────────────────┘
```

- **Sport group:** Name of the sport group (corresponds to the role name in Admidio).
- **Total fees:** Sum of all proportional fee allocations for this sport group.
- **No sport group:** Fees from members who have a fee value but are not assigned
  to any sport group role.
- **Total:** Grand total of all distributed fees.

The table supports sorting (DataTables). Clicking a column header sorts the rows
alphabetically or numerically.

### 6.2 Detail table – share per member

The second table shows the exact fee share per member and sport group:

```
┌────────────┬────────────┬───────────────┬──────────────┬─────────────────┐
│ Last name  │ First name │ Sport group   │ Share (€)    │ Family          │
├────────────┼────────────┼───────────────┼──────────────┼─────────────────┤
│ Baum       │ Jonas      │ Football      │        96.00 │                 │
│ Keller     │ Sara       │ Yoga          │        42.00 │                 │
│ Keller     │ Sara       │ Zumba         │        42.00 │                 │
│ Lindner    │ Elena      │ Judo          │        52.00 │ Family Lindner  │
│ Lindner    │ Finn       │ Judo          │        52.00 │ Family Lindner  │
│ Lindner    │ Nina       │ Judo          │        52.00 │ Family Lindner  │
│ …          │ …          │ …             │            … │                 │
└────────────┴────────────┴───────────────┴──────────────┴─────────────────┘
```

- **Last name / First name:** Member's name (sorted by last name).
- **Sport group:** The sport group this share is allocated to.
- **Share:** Calculated fee share for this specific sport group, in euros.
- **Family:** Name of the family role if the member is billed via a family
  membership. Empty for individual members.

> **Note:** A member appears multiple times in the detail table if they belong to
> multiple sport groups – one row per sport group.

---

## 7. Calculation logic

The plugin distributes membership fees according to the following rules. The logic
is identical to that of the standalone Python script `stats.py`.

### 7.1 Individual members

A member who does **not** belong to any family category role is treated as an
individual member:

```
Share per sport group = Member's fee ÷ Number of active sport groups
```

**Example:**  
Sara Keller pays €84.00 and is active in the sport groups Yoga and Zumba.

```
Share Yoga  = 84.00 ÷ 2 = 42.00 €
Share Zumba = 84.00 ÷ 2 = 42.00 €
```

### 7.2 Family memberships

All members belonging to the same family role share a single fee:

1. **Determine the family fee:** The plugin searches for the first family member with
   a fee value > 0. This value is used as the total fee for the family.
   *(In practice, exactly one family member carries the fee; all others have value 0
   or no entry.)*

2. **Share per family member:**
   ```
   Share per member = Family fee ÷ Number of family members
   ```

3. **Share per sport group:**
   ```
   Share per sport group = Share per member ÷ Member's number of sport groups
   ```

**Example:**  
Family Lindner (3 members: Elena, Nina, Finn) pays a total of €156.00.
All three are active only in the Judo sport group.

```
Share per member       = 156.00 ÷ 3 = 52.00 €
Share Judo (Elena)     =  52.00 ÷ 1 = 52.00 €
Share Judo (Nina)      =  52.00 ÷ 1 = 52.00 €
Share Judo (Finn)      =  52.00 ÷ 1 = 52.00 €
Total Judo (Family Lindner) = 156.00 €
```

**Extended example with multiple sport groups:**  
Family Fox (2 members: Lea, Ben) pays €108.00.
Lea is active in Yoga and Zumba, Ben only in Football.

```
Share per member = 108.00 ÷ 2 = 54.00 €

Lea:
  Share Yoga     = 54.00 ÷ 2 = 27.00 €
  Share Zumba    = 54.00 ÷ 2 = 27.00 €

Ben:
  Share Football = 54.00 ÷ 1 = 54.00 €
```

### 7.3 Members without sport group assignment

If a member has a fee but is **not assigned to any sport group**, their fee (or
proportional share in the case of family members) is recorded in the **"No sport
group"** row of the summary table. This row only appears when at least one such
amount exists.

### 7.4 Worked example – complete overview

| Member | Type | Fee | Sport groups | Share Football | Share Yoga | Share Zumba |
|---|---|---|---|---|---|---|
| Baum, Jonas | Individual | €96.00 | Football | €96.00 | – | – |
| Keller, Sara | Individual | €84.00 | Yoga, Zumba | – | €42.00 | €42.00 |
| Lindner, Elena | Family (Lindner) | *€156.00 total* | Judo | – | – | – |
| Lindner, Nina | Family (Lindner) | | Judo | – | – | – |
| Lindner, Finn | Family (Lindner) | | Judo | – | – | – |

*The "Judo" column totals €156.00.*

---

## 8. Access rights in detail

The plugin uses two independent security layers:

### Layer 1 – Menu entry (Admidio standard)

The menu entry itself is only visible to selected roles. Members without the required
role do not see the menu item at all, and direct access to the plugin URL is also
blocked by Admidio's menu access check.

**Configuration:** Administration → Menu → Edit entry → "Visible to roles"

### Layer 2 – Plugin setting (section 5.2)

As an additional safeguard, the plugin itself checks upon each request whether the
currently logged-in member belongs to one of the roles configured in the settings.
If the right is missing, only the message "No permission" is displayed – without any
further details.

**Configuration:** Administration → Plugins → Beitragsanalyse → Settings →
"Visible to roles"

### Recommended configuration

Both layers should be restricted consistently to the same roles, for example
"Board" and "Treasurer":

```
Menu entry → Visible to roles:    Board, Treasurer
Plugin setting → Visible to:      Board, Treasurer
```

---

## 9. Tips and troubleshooting

**Problem:** The plugin displays "The plugin is not fully configured yet."

→ Open the plugin settings and select at least the **sport group category** and the
**fee profile field**. Both are required fields.

---

**Problem:** The tables are empty even though fees and role memberships exist.

→ Check that the membership in the sport group roles is **active**, i.e. the end
date of the role membership lies in the future. Expired memberships are not included.

→ Check that the fee is entered in the member's profile as a positive number. Empty
fields and the value 0 are not included.

---

**Problem:** The "No sport group" total is unexpectedly high.

→ Some members have a fee but are not assigned to any role in the sport group
category. Open the detail table and filter for an empty sport group column to
identify these members.

---

**Problem:** Family members are treated as individual members.

→ Check that the family membership category is selected in the plugin settings.

→ Check that all family members are assigned to the same family role.

→ Check that **exactly one** family member has a fee value > 0. If no member has
a value, the family is ignored entirely.

---

**Problem:** The plugin does not appear in the Plugin Manager list.

→ Make sure the folder `beitragsanalyse` is located directly inside `plugins/`
and contains the file `beitragsanalyse.json`.

→ Check file permissions: the web server must be able to read the files.

---

**Problem:** "Module disabled" message despite correct settings.

→ Open the plugin settings and set **Plugin enabled** to **"Enabled"**.

---

## 10. Changelog

### Version 1.0.0 – April 2026

- Initial release
- Proportional fee distribution across sport groups (individual members)
- Special handling of family memberships
- Summary table per sport group
- Detail table per member with family indicator
- Role-based access control (two layers)
- Plugin settings: sport group category, family category, fee profile field,
  visible to roles
- Bilingual interface (German, English)
- Compatible with Admidio 5.1.0+

---

*This plugin is released under the GNU General Public License v2.0.*  
*Admidio is a registered trademark of the Admidio team.*
