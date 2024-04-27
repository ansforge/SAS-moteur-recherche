export default function aggregV2toV1(slot) {
  return {
    start: slot.startDate ?? null,
    end: slot.endDate ?? null,
    consultation_type: slot.consultationType ?? [],
    slot_reservation_link: slot.reservationLink ?? [],
  };
}
