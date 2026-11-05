tf-pilot-r2 — Operations faults fixed, allocation reserved to Operations, supplier bank details protected (11 Oct 2026)
=================================================================================================================================
Development system only, over tf-paydesk-r5 + tf-pilot-r1. No database change. 13 files: 12 changed, 1 new.

1. DRIVER ALLOCATION PAGE (dashboard2/transport/driver_allow/tour_transport)
   Checked first: the page allocates ONE tour (?tour_id=…); the existing "Tours pending driver allocation" list
   (dashboard2/transport/driver_allow/pending_tours) links each tour to it. Opened without a tour — from its own menu entry —
   the view failed ("Undefined array key tour_id", for every user incl. the administrator). It now goes to that existing
   pending list (no new page, no second workflow); with a tour it works as before. DriverAllocationController::tourTransport.

2. CREW REPLACEMENT (and DRIVER ADVANCE) ENTRIES
   Checked first: "Crew replacement" (tour/transport_ops/crew_change) and "Driver advance" (…/driver_advance) are privilege
   entries of the Transport Desk (schema 24/26): "Replace…" on an engaged crew line and the advance/settlement on a card. They
   had no page, so a link to them answered 404. They now open the Transport Desk where that work is (?focus=…, with a hint);
   a sign-in without the privilege is refused and recorded. Routes.tour_flow.php, TourDeskController::crewChangePage /
   driverAdvancePage, transport_ops view (hint).

3. ALLOCATION ONLY BY OPERATIONS
   Engaging a driver or guide on a card, setting its vehicle and the days a person works now need the Driver Allocation
   privilege (Desks::ALLOCATION_URL = the legacy Driver Allocation entry, held by Pilot / Operations Allocation 36, the
   administrator and the legacy groups Operational 13, Maintenance 15, Transport Manager 23, Admin 25) — checked on the server
   before anything is read, recorded when refused. Two EXISTING groups allocate today through Transport Operations alone and
   keep exactly that (no existing access changed): Management 21 and Transport & Crew Guide Allocation Desk 28
   (Desks::LEGACY_ALLOCATION_GROUPS — remove them from the list when their people move to the departments). Transport &
   Guide Handling (33) keeps the cards, documents, mileage and driver advances, but cannot allocate; the page shows the
   allocation controls disabled. The legacy allocate/remove endpoints already require the Driver Allocation privilege.
   Mid-tour replacement still needs Crew replacement (held by Operations 36 and Management 21).

4. SUPPLIER BANK DETAILS (Hotel Reservations and every other sign-in that neither pays nor verifies)
   Who sees and changes bank details: Perms::bankDetails() — Accounts (bank initiation), the Bank Authoriser, the Supplier
   Bank Details master, the verifier, the Avenir bank-account master, the administrator (unchanged from tf-pilot-r1).
   • legacy Supplier master (master_files/supplier…): the list (Supplier Report), the edit form and the supplier lookup used
     by the booking pages show the account number to its last four digits; the contacts (name, address, phones, e-mail,
     representative, credit days, remarks) stay visible and editable; on update the stored bank fields (account number,
     bank, branch, account holder) are kept whatever is sent, on create they stay empty for Accounts; a sent change is
     recorded (access_denied, previous values kept in the record); the form shows the bank fields locked with a notice.
     Exports of the list are made in the browser from the masked page; there is no server export route.
   • Tour Payment Dashboard: the desk JSON and the legacy tour search JSON mask every bank account number (Mask::bank).
   • A payment request made from the dashboard (tour/tour_accounts/payment_request) takes the supplier's bank details from the
     supplier master on the server when the sign-in may not see them or the browser sent a masked number — never as sent
     (this also closes a door: a requester could previously put any account number into a request).
   Hotel Reservations' access to the legacy Supplier master (menus 29 Supplier, 32 Supplier Report) was removed before this
   kit was installed; it is restored only after the protection is verified on the development system (see the report).

5. TESTS (cloud test copy; tests/ops_conf_test.py, evidence/)
   80 Driver Allocation page with and without a tour (Operations, administrator), refused to the other departments
   81 Crew replacement / Driver advance entries open the desk for holders; refused and recorded otherwise
   82 Transport & Guide Handling cannot engage crew, set the vehicle, set the days or replace (403, recorded); Operations,
      the administrator and the legacy allocation group can; the page flags CAN_ALLOCATE accordingly
   83 the legacy allocate endpoint refuses the departments without the privilege
   84 supplier master: masked list / lookup / form for Hotel Reservations, contacts kept; a direct bank change ignored and
      recorded; a new supplier created without bank details; Accounts sees and changes the full number
   85 dashboard JSON masked for Hotel Reservations, full for Accounts; a request from the masked page stores the master's
      bank details
   86 Pilot shortcuts: Operations gets Driver Allocation (→ pending list) and Crew replacement (→ desk); Transport doesn't
   Results: r4 suite 202/202, r5 bulk 71/71, pilot 36/36, this kit 33/33.

6. INSTALL / ROLL BACK
   python3 apply.py check / apply / rollback  C:\wamp64\www\avenir — no database step; users sign out and in once.
