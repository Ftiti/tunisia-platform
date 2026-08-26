<?php
namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{
    public function create(array $data): Booking;
    public function findById(int $id): ?Booking;
    public function update(int $id, array $data): Booking;
    public function getByFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function getActiveBookingsForSlot(int $providerId, string $date, string $time, string $endTime, ?int $excludeId = null): int;
}
