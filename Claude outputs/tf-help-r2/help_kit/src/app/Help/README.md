# The in-system User Manual (tf-help-r2)

Every screen carries a **User Manual** button (bottom right). It opens **the manual for that screen** — each
section of the system has its own (so far **Hotel Rates** and **Hotel Master**). Staff type a question in plain
words and get short numbered steps with the exact screen and button names, a button that opens the right screen,
and — when they lack the permission — who can do it instead. Each manual has its own printable PDF; the full
manual puts every section together.

**One source:** `app/Help/sections/*.json`, one file per section. The panel, the topic lists and the PDFs are
all made from these files, so they never disagree. Pictures live in `app/Help/images/<section id>/`.

## Which manual opens where

* A screen belongs to the sections whose `home_pages` hold it: Supplier → Hotel Master; Hotel Rate Approval and
  Hotel Incomplete Work → Hotel Rates.
* Two sections share **Hotel Rates (new)**. The Hotel Master's `detect` rule picks it while the **Hotel details**,
  **Room types** or **History** tab (or the Duplicates window) is open; otherwise the Hotel Rates manual opens.
  The panel shows **Manuals for this screen** to switch by hand.
* A screen without a manual of its own (Tour Inquiry, the dashboard…) says so, lists the topics from other
  manuals that help there (`pages`), and offers **Other manuals**.
* Questions are answered from the open manual first; an answer from another manual is marked
  *from the … manual*. A question about a part of the system no manual covers yet gets *No checked answer*.

PDFs: `help/manual/<section id>` (for example `help/manual/hotel_master`), and `help/manual` for the full manual.
Add `?download=1` to save instead of view.

## Rules

1. **Nothing is guessed.** A question is answered only from these topics. When no topic fits clearly the
   panel says there is no checked answer, offers the nearest topics and the PDF.
2. **Help never changes data.** All help routes are GET and read-only; links only open screens.
3. **Exact names.** Write screen, tab and button names exactly as the screen shows them, in `**bold**`.
4. **Permissions.** `needs` lists the menu URLs (permissions) an action needs; a step that needs more than
   the topic (for example approving) carries its own `needs`. The panel shows each person what they may do.
5. **Every topic is verified.** Its `verify` list names the words, routes and menu entries it relies on.

## When the system changes (keep it updated)

After any release that touches a screen:

```
php spark help:check          # every topic against the code: words on screens, routes, menu entries, pictures
php spark help:check --pdf    # the same, and rebuild the printable manual
```

or, as System Administrator, open **User Manual → run the check** (`help/check`). A topic marked CHECK
quotes something that is no longer on the screen: fix the words in its section file and set the section's
`checked` date. The PDF rebuilds itself the next time it is opened (its file name carries a fingerprint
of the sections and pictures).

## Adding a section (the next manual)

Copy the shape of `hotel_master.json`: `id`, `title`, `order`, `checked`, `pages` (screens whose help
lists these topics), `home_pages` (the screens whose own manual this is), optional `detect` (for a screen
shared with another section: `{page, active}` where `active` is a CSS selector that is present while this
section's part of the screen is open), `intro`, `examples` (the "Try asking" questions), `menus` (menu URL →
name), an optional `guide` (step-by-step stages with pictures, used by the PDF) and `topics`. The new manual
gets its panel, its PDF and its place in the full manual by itself.

A topic: `id`, `title`, `questions` (how staff ask it), `keywords` (meaning words with weights — see
`HelpManual::WORDS`; weight ≥ 3 marks what the topic is mainly about), `pages`, `audience`, `needs`,
`needs_note`, `summary`, `steps` (`text`, optional `link` {path, label}, optional `needs`), `notes`,
`images`, `related`, `manual_ref`, `verify`.

New words staff use go into `HelpManual::WORDS` / `PHRASES`. Words of parts of the system no manual covers yet
are in the `elsewhere` group (driver, vehicle, voucher, pay…): a question with one of them is never answered from
an unrelated topic. When the manual for that part is written, take its words out of `elsewhere`.
Questions asked are logged (no names; with the screen and the manual that was open) in
`writable/help/questions-YYYY-MM.log` — read it to see what people ask that the manual does not answer.
