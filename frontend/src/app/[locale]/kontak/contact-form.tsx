"use client";

import { useState } from "react";
import { sendContact } from "@/lib/contact";

const field = "field";

function Label({ htmlFor, children, optional }: { htmlFor: string; children: React.ReactNode; optional?: boolean }) {
  return (
    <label htmlFor={htmlFor} className="mb-1.5 block text-xs font-semibold text-ink-soft">
      {children}
      {optional && <span className="ml-1 font-normal text-ink-muted">(opsional)</span>}
    </label>
  );
}

export function ContactForm() {
  const [status, setStatus] = useState<"idle" | "sending" | "ok" | "error">("idle");
  const [message, setMessage] = useState("");

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = Object.fromEntries(new FormData(form)) as Record<string, string>;

    setStatus("sending");
    try {
      const res = await sendContact(data);
      setStatus("ok");
      setMessage(res.message);
      form.reset();
    } catch (err) {
      setStatus("error");
      setMessage(err instanceof Error ? err.message : "Terjadi kesalahan. Coba lagi.");
    }
  }

  return (
    <form
      onSubmit={onSubmit}
      className="rounded-2xl border border-border bg-surface p-6 shadow-sm sm:p-8"
    >
      <h2 className="text-h2">Kirim Pesan</h2>
      <p className="mt-1 text-sm text-ink-muted">Kolom bertanda * wajib diisi.</p>

      <div className="mt-6 space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <Label htmlFor="cf-name">Nama lengkap *</Label>
            <input id="cf-name" name="name" required autoComplete="name" placeholder="mis. Budi Santoso" className={field} />
          </div>
          <div>
            <Label htmlFor="cf-email">Email *</Label>
            <input id="cf-email" name="email" type="email" required autoComplete="email" inputMode="email" placeholder="nama@email.com" className={field} />
          </div>
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <Label htmlFor="cf-phone" optional>No. telepon</Label>
            <input id="cf-phone" name="phone" type="tel" autoComplete="tel" inputMode="tel" placeholder="08xx xxxx xxxx" className={field} />
          </div>
          <div>
            <Label htmlFor="cf-subject" optional>Subjek</Label>
            <input id="cf-subject" name="subject" placeholder="Perihal pesan" className={field} />
          </div>
        </div>
        <div>
          <Label htmlFor="cf-message">Pesan *</Label>
          <textarea
            id="cf-message"
            name="message"
            required
            rows={5}
            placeholder="Tulis pertanyaan atau masukan Anda…"
            className="field"
          />
        </div>

        {/* Honeypot anti-spam — disembunyikan dari manusia, bot mengisinya.
            Backend menolak submit bila terisi (lihat ContactController). */}
        <div aria-hidden className="absolute -left-[9999px] h-0 w-0 overflow-hidden" tabIndex={-1}>
          <label htmlFor="cf-website">Website (kosongkan)</label>
          <input id="cf-website" name="website" type="text" autoComplete="off" tabIndex={-1} />
        </div>
      </div>

      <button
        type="submit"
        disabled={status === "sending"}
        className="btn-glow mt-5 inline-flex min-h-12 items-center justify-center rounded-xl bg-brand px-7 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
      >
        {status === "sending" ? "Mengirim…" : "Kirim Pesan"}
      </button>

      <div aria-live="polite" className="mt-4 empty:mt-0">
        {status === "ok" && (
          <p role="status" className="rounded-xl border border-success/25 bg-success-soft px-4 py-3 text-sm font-medium text-success">
            {message}
          </p>
        )}
        {status === "error" && (
          <p role="alert" className="rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm font-medium text-danger">
            {message}
          </p>
        )}
      </div>
    </form>
  );
}
