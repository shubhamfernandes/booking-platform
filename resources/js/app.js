import './bootstrap';

import BookingForm from "./modules/booking-form.js";

document.addEventListener("DOMContentLoaded", () => {
  const form = new BookingForm("bookingForm");
  form.init();
});
