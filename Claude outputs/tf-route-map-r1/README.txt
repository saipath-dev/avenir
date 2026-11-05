tf-route-map-r1 — Driver's route map in the Driver's Pack (10 Oct 2026, Shantha's request)
============================================================================================
NOT DEPLOYED. Built and tested on the test copy; samples in shots\. Shantha reviews, then says "put it on live".

What the driver gets
  * A map of Sri Lanka with the tour's stops as numbered red pins (filled = overnight stop, hollow = passed through),
    the planned route as a blue line with direction arrows, the planned kilometres on each sector, the day(s) at each
    stop, ✈ at the airport. Towns sit at their true position on a simplified coastline; the lines are straight
    ("schematic"), not the road.
  * Beside the map, the days: route, planned km, extra sightseeing allowance, the night's hotel.
  * Under it, day by day: every leg (from → to, planned km, extra), AIRPORT PICKUP / DROP-OFF, GARAGE RUN, CITY TOUR,
    STAY DAY tags, the day total, the night hotel and area; then the total planned mileage, with the extra sightseeing
    allowance shown separately, and garage runs counted and said.
  * Header and footer: tour number and name, dates, pax, vehicle, driver, the REVISION date (the card's last change)
    and the print time, "planned mileage — actual driving may vary", "a newer revision replaces this sheet".
  * Nothing else: no rate, no price, no margin, no bata, no booking state, no control. Read only.

Where it is
  Transport Operations → the tour's card → Driver's pack → "Route map": Preview (the sheet), PDF, Image.
    Preview  tour/transport_ops/card/<job>/pack/ROUTE          the sheet, with "Download picture (WhatsApp)" on top
    Image    …/pack/ROUTE?img=1                                 one PNG, 1080 px wide: header, map, day table, totals
    PDF      …/pack/ROUTE?pdf=1                                 A4, two pages (map on page 1, table after)
    Map      …/pack/ROUTE?map=1                                 the map alone (the sheet shows it)
  The picture is drawn on the server (PHP GD, DejaVu font shipped under public/assets/fonts/dejavu/); the PDF uses the
  TCPDF library already in vendor/ (loaded directly, since composer's class map on the server does not list it).

ONE STEP BY HAND after the files are on (the kit writes nothing in the database):
  Transport Operations → "Driver's pack list" → add  code ROUTE · name Route map · before the tour.
  From then on every card whose pack is not yet issued shows "Route map" (pending → Issued / Waive like the others),
  and every new card gets it. Cards already issued are not changed. Without the entry the sheet still opens at the
  Preview address above, but the pack row is not shown.

Places the map does not know
  A place is put on the map when its town (the last part of "Hotel, Road, Town", or a known alias) is in the
  gazetteer in RouteMap.php (about 130 towns and airport-hotel spellings). An unknown place is listed under the map as
  "Not on the map: …" and in the table, never guessed onto the map. To add a town: its name and [lat, lon] in
  RouteMap::TOWNS; another spelling in RouteMap::ALIASES.

Files
  app/Libraries/TourFlow/RouteMap.php          NEW  the data (from tf_transport_leg) and the picture
  app/Views/pages/tour/driver_route.php        NEW  the sheet (print page and PDF body)
  app/Libraries/TourFlow/TourService.php            packGenerator: ROUTE → route; packDocument: the route data;
                                                    hydrateJob: a document added to the master list joins every open card
  app/Controllers/Tour/TourDeskController.php       packDocument → routeDocument(): sheet / map / image / PDF
  app/Views/pages/tour/transport_ops.php            the pack row: Preview · PDF · Image for the route map
  public/assets/fonts/dejavu/*.ttf (+LICENSE)  NEW  the font the picture is drawn with (DejaVu, free licence)

Tested on the test copy (tests\test_route.py, Playwright; tests\try.php draws straight from the database):
  the pack row with its three buttons; the sheet (200); the image (PNG, attachment, 1080×2720 for an 8-day tour);
  the map (PNG); the PDF (2 pages, A4, Unicode font); the sheet on a 390 px phone. Three sample tours drawn
  (12, 5 and 8 days) including hand-typed places ("katunayeka", "Good wood plaza") and a four-town cluster
  (Dambulla / Kandalama / Sigiriya / Polonnaruwa) — labels placed clear of one another with leader lines.
