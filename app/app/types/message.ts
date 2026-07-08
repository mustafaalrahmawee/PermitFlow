/**
 * UC-10 message-thread types, mirroring the exchange-request-messages API seam.
 * No fetch logic here — the messages Pinia store owns the seam calls. The send
 * seam's only field is `body`; the returned message carries its sender and
 * recipient so the thread can be attributed to the citizen or the responsible
 * staff member [docs/by-use-case/uc10_exchange-request-messages.md API seam].
 */
export interface ThreadMessage {
  id: number;
  request_id: number;
  sender_user_account_id: number;
  recipient_user_account_id: number;
  message_kind: string;
  body: string;
  sent_at: string | null;
}
