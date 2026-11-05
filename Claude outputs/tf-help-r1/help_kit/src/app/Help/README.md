# The in-system User Manual (tf-help-r1)

Every screen carries a **User Manual** button (bottom right). Staff type a question in plain words and get
short numbered steps with the exact screen and button names, a button that opens the right screen, and —
when they lack the permission — who can do it instead. The same instructions make the printable PDF.

**One source:** `app/Help/sections/*.json`. The help panel, "Help for this screen" and the PDF are all made
from these files, so they never disagree. Pictures live in `app/Help/images/<section>/`.

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

## Adding a section

Copy the shape of `hotel_rates.json`: `id`, `title`, `order`, `checked`, `pages` (screens whose help
lists these topics), `home_pages` (screens that belong to the section), `intro`, `menus` (menu URL →
name), an optional `guide` (step-by-step stages with pictures, used by the PDF) and `topics`.

A topic: `id`, `title`, `questions` (how staff ask it), `keywords` (meaning words with weights — see
`HelpManual::WORDS`; weight ≥ 3 marks what the topic is mainly about), `pages`, `audience`, `needs`,
`needs_note`, `summary`, `steps` (`text`, optional `link` {path, label}, optional `needs`), `notes`,
`images`, `related`, `manual_ref`, `verify`.

New words staff use go into `HelpManual::WORDS` / `PHRASES`. Questions asked are logged (no names) in
`writable/help/questions-YYYY-MM.log` — read it to see what people ask that the manual does not answer.
