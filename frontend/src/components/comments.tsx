"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import type { Comment, CommentThread } from "@/lib/types";
import { postComment, type CommentPayload } from "@/lib/comments";
import { formatDate } from "@/lib/format";

const field = "field";

function initials(name: string) {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
}

function CommentNode({
  comment,
  depth,
  onReply,
  activeReply,
}: {
  comment: Comment;
  depth: number;
  onReply: (id: number | null) => void;
  activeReply: number | null;
}) {
  return (
    <li className={depth > 0 ? "mt-4 border-l border-border pl-4 sm:pl-6" : "border-t border-border pt-6 first:border-t-0 first:pt-0"}>
      <div className="flex items-start gap-3">
        <span
          aria-hidden
          className="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-light text-xs font-bold text-brand-dark"
        >
          {initials(comment.author_name)}
        </span>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
            <span className="text-sm font-semibold text-ink">
              {comment.author_url ? (
                <a href={comment.author_url} target="_blank" rel="nofollow noopener noreferrer" className="hover:text-brand-dark">
                  {comment.author_name}
                </a>
              ) : (
                comment.author_name
              )}
            </span>
            {comment.is_staff && (
              <span className="rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-on-brand">
                Pengelola
              </span>
            )}
            <span className="text-xs text-ink-muted">{formatDate(comment.created_at)}</span>
          </div>
          <p className="mt-1 whitespace-pre-line text-sm text-ink-soft">{comment.body}</p>
          <button
            type="button"
            onClick={() => onReply(activeReply === comment.id ? null : comment.id)}
            className="mt-2 text-xs font-semibold text-brand-dark hover:underline"
          >
            {activeReply === comment.id ? "Batal membalas" : "Balas"}
          </button>
        </div>
      </div>

      {comment.replies.length > 0 && (
        <ul>
          {comment.replies.map((r) => (
            <CommentNode
              key={r.id}
              comment={r}
              depth={depth + 1}
              onReply={onReply}
              activeReply={activeReply}
            />
          ))}
        </ul>
      )}
    </li>
  );
}

export function Comments({ slug, thread }: { slug: string; thread: CommentThread }) {
  const requireEmail = thread.require_email;
  const router = useRouter();
  const [replyTo, setReplyTo] = useState<number | null>(null);
  const [status, setStatus] = useState<"idle" | "sending" | "ok" | "error">("idle");
  const [message, setMessage] = useState("");

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const raw = Object.fromEntries(new FormData(form)) as Record<string, string>;

    const payload: CommentPayload = {
      author_name: raw.author_name,
      author_email: raw.author_email || undefined,
      author_url: raw.author_url || undefined,
      body: raw.body,
      website: raw.website || undefined,
      parent_id: replyTo ?? undefined,
      subscribe: raw.subscribe === "on" || undefined,
    };

    setStatus("sending");
    try {
      const res = await postComment(slug, payload);
      setStatus("ok");
      setMessage(res.message);
      form.reset();
      setReplyTo(null);
      // Komentar yang auto-approved langsung tampil setelah ISR menyegarkan.
      if (res.approved) router.refresh();
    } catch (err) {
      setStatus("error");
      setMessage(err instanceof Error ? err.message : "Terjadi kesalahan. Coba lagi.");
    }
  }

  return (
    <section id="komentar" className="scroll-mt-24">
      <h2 className="text-h2 heading-rule mb-6">
        {thread.count > 0 ? `${thread.count} Komentar` : "Komentar"}
      </h2>

      {thread.data.length > 0 ? (
        <ul className="space-y-6">
          {thread.data.map((c) => (
            <CommentNode
              key={c.id}
              comment={c}
              depth={0}
              onReply={setReplyTo}
              activeReply={replyTo}
            />
          ))}
        </ul>
      ) : (
        <p className="text-sm text-ink-muted">Belum ada komentar. Jadilah yang pertama berkomentar.</p>
      )}

      {thread.open ? (
        <form
          onSubmit={onSubmit}
          className="mt-8 rounded-2xl border border-border bg-surface p-6 shadow-sm sm:p-8"
        >
          <h3 className="text-lg font-bold text-ink">
            {replyTo ? "Tulis balasan" : "Tinggalkan komentar"}
          </h3>
          <p className="mt-1 text-sm text-ink-muted">
            Email tidak dipublikasikan. Komentar ditinjau sebelum tampil.
          </p>

          <div className="mt-5 space-y-4">
            <div>
              <label htmlFor="c-body" className="mb-1.5 block text-xs font-semibold text-ink-soft">
                Komentar *
              </label>
              <textarea id="c-body" name="body" required rows={4} className="field" placeholder="Tulis komentar Anda…" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="c-name" className="mb-1.5 block text-xs font-semibold text-ink-soft">
                  Nama *
                </label>
                <input id="c-name" name="author_name" required autoComplete="name" className={field} placeholder="Nama Anda" />
              </div>
              <div>
                <label htmlFor="c-email" className="mb-1.5 block text-xs font-semibold text-ink-soft">
                  Email {requireEmail ? "*" : <span className="font-normal text-ink-muted">(opsional)</span>}
                </label>
                <input id="c-email" name="author_email" type="email" required={requireEmail} autoComplete="email" inputMode="email" className={field} placeholder="nama@email.com" />
              </div>
            </div>
            <div>
              <label htmlFor="c-url" className="mb-1.5 block text-xs font-semibold text-ink-soft">
                Situs web <span className="font-normal text-ink-muted">(opsional)</span>
              </label>
              <input id="c-url" name="author_url" type="url" autoComplete="url" className={field} placeholder="https://" />
            </div>

            {thread.subscriptions_enabled && (
              <label className="flex items-start gap-2.5 text-sm text-ink-soft">
                <input
                  type="checkbox"
                  name="subscribe"
                  className="mt-0.5 h-4 w-4 shrink-0 rounded border-border text-brand focus:ring-brand"
                />
                <span>
                  Beri tahu saya lewat email bila ada balasan untuk komentar ini.
                  {!requireEmail && (
                    <span className="text-ink-muted"> Isi email di atas agar aktif.</span>
                  )}
                </span>
              </label>
            )}

            {/* Honeypot anti-spam — disembunyikan dari manusia. */}
            <div aria-hidden className="absolute -left-[9999px] h-0 w-0 overflow-hidden" tabIndex={-1}>
              <label htmlFor="c-website">Website (kosongkan)</label>
              <input id="c-website" name="website" type="text" autoComplete="off" tabIndex={-1} />
            </div>
          </div>

          <div className="mt-5 flex items-center gap-3">
            <button
              type="submit"
              disabled={status === "sending"}
              className="btn-glow inline-flex min-h-12 items-center justify-center rounded-xl bg-brand px-7 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
            >
              {status === "sending" ? "Mengirim…" : replyTo ? "Kirim balasan" : "Kirim komentar"}
            </button>
            {replyTo && (
              <button type="button" onClick={() => setReplyTo(null)} className="text-sm font-semibold text-ink-muted hover:text-ink">
                Batal
              </button>
            )}
          </div>

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
      ) : (
        <p className="mt-8 rounded-xl border border-border bg-surface-muted px-4 py-3 text-sm text-ink-muted">
          Komentar untuk artikel ini ditutup.
        </p>
      )}
    </section>
  );
}
