tf-mileage-track-r1 — Mileage by sector as the tour's journey (10 Oct 2026, Shantha's request)
=================================================================================================
NOT DEPLOYED. Built and tested on the test copy; screenshots in shots\. Shantha reviews, then says "put it on live".

What it does
  * Railway view (the default): a station for every place the vehicle goes — its day and date, its nights there
    (from the itinerary), a small picture where the system has one — and only the kilometres on the line between
    stations. The line's length follows the kilometres. A dashed grey line is the garage run; green dashed is a leg
    waived by an excursion with its own transport; red dashed is a leg with no distance. A small amber triangle on a
    sector says past tours drove a fifth or more away from Google's figure.
  * Point at a station or a sector (tap on a phone): a window with everything the list showed — quoted figure and
    where it came from, Google km, actual km with its journeys and the type-km box, extra km and its box, the
    allowance for a garage run / stay day / city tour, the airport rule, the notes, the warnings, Edit / Delete /
    Release / Restore, and a Google Maps link. Click or tap keeps it open so a figure can be typed; × / Esc / a click
    elsewhere closes it; after a save the screen is drawn again and the window comes back.
  * One line per day under the track (route, the day's km, number of sectors), opened for the day's legs.
  * Total tour mileage bar: journey km, charged km, extra, waived, the amount, Google against the driven record.
  * List view: the previous screen, byte for byte the same code (the choice is remembered on this browser).
  * Map view: the tour's places in order on Google Maps, in a new tab (Google takes up to ten stops).
  * Pictures: a Photo Library photo whose tag is named for the town (the airport takes "Arrival & departure"),
    else the Places master's picture of a site in that town; read only. No picture → a plain station with an icon.

What it does not do
  * No calculation, rule, exception or saved figure is changed: the legs and every figure come from the same
    IntakeService read; the boxes and buttons post to the same endpoints as before.
  * Nothing written for the pictures; no new table, no new setting, no new route.

Files (3)
  app/Views/pages/tour/intake.php           the Railway view, its window, the view switch (CSS + JS in the view)
  app/Libraries/TourFlow/PhotoLibrary.php   forStations(): the station pictures, read only
  app/Libraries/TourFlow/IntakeService.php  get(): station_photos on the inquiry read

Tested on the test copy (tests\shots.py, Playwright): hover opens / leaves closes; click pins; typing 41 km in the
window saves through the existing /actual endpoint and the window comes back with 41; Esc closes; day rows open;
List view shows the same 14 rows; Map link; phone (390 px, touch): tap opens the window as a bottom sheet.
No JavaScript errors of this change (the only console entries are the test host's blocked fonts/CDNs).
