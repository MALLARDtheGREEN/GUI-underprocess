# Approved design

The accepted reference is `terminal-states-design-v3.png`. Superseded concept
images have been removed. The two CSS files in `public/vendor/` retain the used
typography, layout and button declarations from `D:/lab-workspace/Workplace`,
with the original attribution. Unused plotting, overlay, sidebar and utility
selectors, and declarations replaced by the detector styles, have been removed.
The Workplace source files are unchanged. `public/styles.css` contains the form,
terminal, accessibility and responsive rules; duplicate declarations and
overrides made unnecessary by the smaller vendor subset have been removed.

- Full-width white main pane and Helvetica/Arial type. The unused right-hand
  gainsboro sidebar and its divider have been removed at the user's request.
- Black native rectangular inputs; nine vertical rows; Setup/Acquire bottom left.
- Blank integer fields, `select a number` dropdowns, `.json file` placeholders.
- No steps, cards, header banner, initial graph, or sidebar Console.
- Bottom docked Python service tab; dark log output; red service errors and a red
  exclamation mark when the terminal is collapsed with an unread service error.
  Opening the terminal acknowledges existing errors; errors arriving while it is
  open are already read. A new error after collapse restores the exclamation mark.
- Validation errors appear in place of grey inline hints;
  they never enter the terminal or trigger the service indicator.

Intentional additions: keyboard focus outlines and scrolling, wrapping hints on
small screens, and a Check sudo button visible only in the expanded terminal to
make the originally requested privileged-operation boundary accessible.

The waiting view opens after an acknowledged preview start; hardware integration
is pending and the preview is explicitly labelled. The main pane
can scroll when long errors or an open terminal reduce its available space.

Latest requested changes: Setup is always blue. Acquire is grey until a successful
Setup and a one-second wait, then green. A grey click reports that preparation is
required and cannot start acquisition. Filename is below both buttons, initially
blank, and generated at Setup in the specified minute-hour-day-month format.

## Visual verification

Compared the approved concept and browser captures: black native controls,
vertical field order, lower-left actions, and the docked dark terminal are
preserved. Later requested changes account for the blue Setup, grey/green Acquire,
filename below the actions, and removal of the empty right-hand sidebar. Long
error text replaces the same hint column. Narrow layouts wrap without horizontal
overflow.

The in-app browser verified interactions but produced scaled screenshots on this
display. A separate headless Edge/Playwright check verified 1280x720 and 390x844
layouts and captured the final evidence without that scaling issue. It also
verified preparation gating, filename generation/editing, preview start/stop,
and the separation of field errors from terminal errors, with no JavaScript errors.
