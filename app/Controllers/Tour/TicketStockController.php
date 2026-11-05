<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\TicketStock;

/**
 * Tour Desk → Ticket Stock — tour/ticket_stock (schema 37, tf-tickets-r1, point 8)
 *
 * The entrance tickets the company holds, under control: the attractions (from the Entrance Fee master) handled
 * from stock or paid at the gate, the batches received, every ticket number with its status (available,
 * allocated to a tour, used, void), the tours holding tickets, and the counts. Allocating to a tour happens on
 * the Activities desk (Hotel Bookings), by the party size; this screen receives stock, voids a number, marks a
 * finished tour's tickets used and shows what is left.
 *
 * tf-tickets-r2 (schema 38): a batch is received as a purchase — the quantity bought, the date bought / paid and the
 * amount paid with its currency — and the attraction shows the tickets received and the amount paid over its batches.
 *
 * One menu URL = one privilege (UserPermissionFilter). Granted to the System Administrator by the installer and
 * to Management by the desks' grant (Desks::TOUR_DESK_V9); Admin grants it further under Group Privileges.
 */
class TicketStockController extends BaseController
{
    public const URL = 'tour/ticket_stock';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function stock(): TicketStock
    {
        return new TicketStock($this->db(), $this->user());
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e)
    {
        return $this->json(['status' => 400, 'message' => $e->getMessage()]);
    }

    // GET tour/ticket_stock
    public function index()
    {
        $data = $this->menuData();
        $data['user']      = $this->user();
        $data['page_url']  = self::URL;
        $data['installed'] = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= 37 && $this->stock()->available();
            // tf-tickets-r2 (schema 38): each batch carries its purchase — date bought / paid and the amount paid
            $data['purchase']  = $data['installed'] && $this->stock()->hasPurchase();
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/tour/ticket_stock', $data);
    }

    // GET tour/ticket_stock/data — the attractions under control with their counts, the fee master for the picker, the tours holding tickets
    public function data()
    {
        try {
            $s = $this->stock();
            return $this->json(['status' => 200, 'data' => ['attractions' => $s->attractions(), 'fees' => $s->feeMaster(), 'holding' => $s->toursHolding()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/ticket_stock/attraction/(:num)?status=&q= — one attraction: its tickets and batches
    public function attraction($id)
    {
        try {
            $s = $this->stock();
            return $this->json(['status' => 200, 'data' => [
                'attraction' => $s->attraction((int) $id),
                'tickets'    => $s->tickets((int) $id, (string) $this->request->getGet('status') ?: null, (string) $this->request->getGet('q') ?: null),
                'batches'    => $s->batches((int) $id),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/ticket_stock/attraction {fee_id | place_id (schema 39), control, low_mark, note, unit_cost, currency} — put an attraction of the master under control
    public function addAttraction()
    {
        try {
            $b = $this->body();
            $a = $this->stock()->addAttraction((int) ($b['fee_id'] ?? 0), (string) ($b['control'] ?? ''), $b['low_mark'] ?? null, $b['note'] ?? null, $b['unit_cost'] ?? null, $b['currency'] ?? null,
                isset($b['place_id']) && (int) $b['place_id'] > 0 ? (int) $b['place_id'] : null);
            return $this->json(['status' => 200, 'message' => $a['name'] . ' is under ticket control: ' . ($a['control'] === 'stock' ? 'tickets from stock. Receive the first batch below.' : 'paid at the gate. The Activities desk marks its lines "pay at gate".'), 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/ticket_stock/attraction/(:num)/update {control?, low_mark?, note?, unit_cost?, currency?, active?}
    public function updateAttraction($id)
    {
        try {
            $a = $this->stock()->updateAttraction((int) $id, $this->body());
            return $this->json(['status' => 200, 'message' => $a['name'] . ' saved.', 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/ticket_stock/attraction/(:num)/receive {prefix, from_no, to_no | numbers, received_on, unit_cost, currency, ref, note}
    public function receive($id)
    {
        try {
            $r = $this->stock()->receive((int) $id, $this->body());
            return $this->json(['status' => 200, 'message' => $r['count'] . ' ticket(s) received: ' . $r['first'] . ' to ' . $r['last'] . '. ' . $r['attraction']['n_available'] . ' now available.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/ticket_stock/ticket/(:num)/void {note}
    public function void($ticketId)
    {
        try {
            $a = $this->stock()->void((int) $ticketId, $this->body()['note'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Ticket marked void; it stays on file with the reason. ' . $a['n_available'] . ' available.', 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/ticket_stock/ticket/(:num)/unvoid {note}
    public function unvoid($ticketId)
    {
        try {
            $a = $this->stock()->unvoid((int) $ticketId, $this->body()['note'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Ticket back in stock. ' . $a['n_available'] . ' available.', 'data' => $a]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/ticket_stock/tour/(:num)/used — every allocated ticket of the tour was used
    public function tourUsed($tourId)
    {
        try {
            $n = $this->stock()->markTourUsed((int) $tourId);
            return $this->json(['status' => 200, 'message' => $n ? $n . ' ticket(s) marked used.' : 'No allocated tickets on that tour.', 'data' => ['count' => $n]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
