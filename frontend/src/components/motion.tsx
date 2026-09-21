"use client";

import {
  useEffect,
  useRef,
  useState,
  type CSSProperties,
  type ElementType,
  type ReactNode,
} from "react";

/* ---------- Scroll progress bar ---------- */

export function ScrollProgress() {
  const ref = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    let raf = 0;
    const update = () => {
      raf = 0;
      const el = ref.current;
      if (!el) return;
      const h = document.documentElement;
      const max = h.scrollHeight - h.clientHeight;
      const p = max > 0 ? h.scrollTop / max : 0;
      el.style.setProperty("--sp", String(p));
    };
    const onScroll = () => {
      if (!raf) raf = requestAnimationFrame(update);
    };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onScroll);
      if (raf) cancelAnimationFrame(raf);
    };
  }, []);

  return <div ref={ref} className="scroll-progress" aria-hidden />;
}

/* ---------- Reveal on scroll ---------- */

type Dir = "up" | "down" | "left" | "right" | "scale" | "none";

const dirVars: Record<Dir, CSSProperties> = {
  up: { ["--rv-y" as string]: "26px" },
  down: { ["--rv-y" as string]: "-26px" },
  left: { ["--rv-x" as string]: "34px", ["--rv-y" as string]: "0px" },
  right: { ["--rv-x" as string]: "-34px", ["--rv-y" as string]: "0px" },
  scale: { ["--rv-s" as string]: "0.94", ["--rv-y" as string]: "0px" },
  none: { ["--rv-y" as string]: "0px" },
};

export function Reveal({
  children,
  as: As = "div",
  delay = 0,
  direction = "up",
  className = "",
}: {
  children: ReactNode;
  as?: ElementType;
  delay?: number;
  direction?: Dir;
  className?: string;
}) {
  const ref = useRef<HTMLElement | null>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduce || !("IntersectionObserver" in window)) return;

    el.classList.add("reveal-armed");

    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1, rootMargin: "0px 0px -6% 0px" },
    );
    io.observe(el);

    const fallback = window.setTimeout(() => el.classList.add("is-visible"), 1600);

    return () => {
      io.disconnect();
      window.clearTimeout(fallback);
    };
  }, []);

  return (
    <As
      ref={ref}
      data-reveal=""
      style={{ ...dirVars[direction], "--reveal-delay": `${delay}ms` } as CSSProperties}
      className={className}
    >
      {children}
    </As>
  );
}

/* ---------- Spotlight + tilt card ---------- */

export function SpotlightCard({
  children,
  className = "",
  tilt = true,
}: {
  children: ReactNode;
  className?: string;
  tilt?: boolean;
}) {
  const ref = useRef<HTMLDivElement | null>(null);

  function onMove(e: React.MouseEvent<HTMLDivElement>) {
    const el = ref.current;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const x = e.clientX - r.left;
    const y = e.clientY - r.top;
    el.style.setProperty("--mx", `${x}px`);
    el.style.setProperty("--my", `${y}px`);
    if (tilt) {
      const rx = ((y / r.height) - 0.5) * -6;
      const ry = ((x / r.width) - 0.5) * 6;
      el.style.setProperty("--tilt-x", `${rx}deg`);
      el.style.setProperty("--tilt-y", `${ry}deg`);
    }
  }

  function onLeave() {
    const el = ref.current;
    if (!el) return;
    el.style.setProperty("--tilt-x", "0deg");
    el.style.setProperty("--tilt-y", "0deg");
  }

  return (
    <div
      ref={ref}
      onMouseMove={onMove}
      onMouseLeave={onLeave}
      className={`spotlight ${tilt ? "tilt" : ""} ${className}`}
    >
      {children}
    </div>
  );
}

/* ---------- Parallax (mouse) ---------- */

export function Parallax({
  children,
  strength = 18,
  className = "",
}: {
  children: ReactNode;
  strength?: number;
  className?: string;
}) {
  const ref = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    if (window.matchMedia("(pointer: coarse)").matches) return;

    let raf = 0;
    let tx = 0;
    let ty = 0;
    const onMove = (e: MouseEvent) => {
      const cx = window.innerWidth / 2;
      const cy = window.innerHeight / 2;
      tx = ((e.clientX - cx) / cx) * strength;
      ty = ((e.clientY - cy) / cy) * strength;
      if (!raf) {
        raf = requestAnimationFrame(() => {
          raf = 0;
          el.style.transform = `translate3d(${tx}px, ${ty}px, 0)`;
        });
      }
    };
    window.addEventListener("mousemove", onMove);
    return () => {
      window.removeEventListener("mousemove", onMove);
      if (raf) cancelAnimationFrame(raf);
    };
  }, [strength]);

  return (
    <div ref={ref} className={`transition-transform duration-300 ease-out ${className}`}>
      {children}
    </div>
  );
}

/* ---------- Count-up number ---------- */

export function CountUp({ value, className = "" }: { value: string; className?: string }) {
  const ref = useRef<HTMLSpanElement | null>(null);
  const [display, setDisplay] = useState(value);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    const match = value.match(/^(\D*)([\d.,]+)(.*)$/);
    if (!match || !("IntersectionObserver" in window)) {
      // Fallback tanpa animasi: langsung tampilkan nilai akhir. Bergantung
      // pada ketersediaan IntersectionObserver yang hanya diketahui di client.
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setDisplay(value);
      return;
    }

    const [, prefix, rawNum, suffix] = match;
    const target = parseFloat(rawNum.replace(/[.,]/g, ""));
    const hasThousands = /[.,]/.test(rawNum);

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      setDisplay(value);
      return;
    }

    const io = new IntersectionObserver((entries) => {
      if (!entries[0].isIntersecting) return;
      io.disconnect();
      const duration = 1300;
      const start = performance.now();
      const tick = (now: number) => {
        const p = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const current = Math.round(target * eased);
        setDisplay(
          `${prefix}${hasThousands ? current.toLocaleString("id-ID") : current}${suffix}`,
        );
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });

    io.observe(el);
    return () => io.disconnect();
  }, [value]);

  return (
    <span ref={ref} className={className}>
      {display}
    </span>
  );
}

/* ---------- Sticky section index (scroll-spy) ---------- */

export function SectionIndex() {
  const [items, setItems] = useState<{ id: string; label: string }[]>([]);
  const [activeId, setActiveId] = useState<string>("");

  useEffect(() => {
    const nodes = Array.from(
      document.querySelectorAll<HTMLElement>("[data-section]"),
    );
    const list = nodes.map((n, i) => {
      if (!n.id) n.id = `sec-${i}`;
      return { id: n.id, label: n.dataset.section ?? `Bagian ${i + 1}` };
    });
    // Daftar section hanya bisa dipindai dari DOM yang sudah ter-render di
    // client; tidak ada sumber lain untuk menurunkan state ini.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setItems(list);
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (list[0]) setActiveId(list[0].id);

    if (!("IntersectionObserver" in window)) return;
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) setActiveId((e.target as HTMLElement).id);
        });
      },
      { rootMargin: "-30% 0px -60% 0px", threshold: 0 },
    );
    nodes.forEach((n) => io.observe(n));
    return () => io.disconnect();
  }, []);

  if (items.length < 3) return null;

  return (
    <div className="sticky top-16 z-30 border-y border-border bg-surface/85 backdrop-blur">
      <div className="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 py-2 sm:px-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        {items.map((it, i) => (
          <a
            key={it.id}
            href={`#${it.id}`}
            className={`shrink-0 rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition ${
              activeId === it.id
                ? "bg-brand text-on-brand"
                : "text-ink-muted hover:bg-surface-muted hover:text-foreground"
            }`}
          >
            <span className="mr-1.5 opacity-60">{String(i + 1).padStart(2, "0")}</span>
            {it.label}
          </a>
        ))}
      </div>
    </div>
  );
}

/* ---------- Back to top ---------- */

export function BackToTop() {
  const [show, setShow] = useState(false);
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    let raf = 0;
    const update = () => {
      raf = 0;
      const h = document.documentElement;
      const max = h.scrollHeight - h.clientHeight;
      setProgress(max > 0 ? Math.min(h.scrollTop / max, 1) : 0);
      setShow(window.scrollY > 700);
    };
    const onScroll = () => {
      if (!raf) raf = requestAnimationFrame(update);
    };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onScroll);
      if (raf) cancelAnimationFrame(raf);
    };
  }, []);

  const r = 20;
  const circ = 2 * Math.PI * r;

  return (
    <button
      type="button"
      onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      aria-label="Kembali ke atas"
      className={`group fixed bottom-24 right-5 z-50 grid h-12 w-12 place-items-center rounded-full bg-brand text-on-brand shadow-md ring-1 ring-black/5 transition-all duration-300 hover:bg-brand-dark hover:scale-105 print:hidden ${
        show ? "translate-y-0 opacity-100" : "pointer-events-none translate-y-4 opacity-0"
      }`}
    >
      <svg
        className="pointer-events-none absolute inset-0 -rotate-90"
        width="48"
        height="48"
        viewBox="0 0 48 48"
        aria-hidden
      >
        <circle cx="24" cy="24" r={r} fill="none" stroke="currentColor" strokeWidth="2.5" className="opacity-20" />
        <circle
          cx="24"
          cy="24"
          r={r}
          fill="none"
          stroke="currentColor"
          strokeWidth="2.5"
          strokeLinecap="round"
          strokeDasharray={circ}
          strokeDashoffset={circ * (1 - progress)}
          style={{ transition: "stroke-dashoffset 0.1s linear" }}
        />
      </svg>
      <svg
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.4"
        strokeLinecap="round"
        strokeLinejoin="round"
        className="relative transition-transform duration-300 group-hover:-translate-y-0.5"
      >
        <path d="M12 19V5M5 12l7-7 7 7" />
      </svg>
    </button>
  );
}

/* ---------- Accordion ---------- */

export function Accordion({
  items,
}: {
  items: { q: string; a: string }[];
}) {
  const [openIdx, setOpenIdx] = useState<number | null>(0);

  return (
    <div className="divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface">
      {items.map((item, i) => {
        const open = openIdx === i;
        return (
          <div key={i}>
            <button
              type="button"
              onClick={() => setOpenIdx(open ? null : i)}
              aria-expanded={open}
              className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-surface-muted sm:px-6"
            >
              <span className="font-bold text-foreground">{item.q}</span>
              <span
                className={`grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-light text-brand-dark transition ${
                  open ? "rotate-45" : ""
                }`}
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round">
                  <path d="M12 5v14M5 12h14" />
                </svg>
              </span>
            </button>
            <div
              className="grid transition-all duration-300 ease-out"
              style={{ gridTemplateRows: open ? "1fr" : "0fr" }}
            >
              <div className="overflow-hidden">
                <p className="px-5 pb-5 text-sm leading-relaxed text-ink-muted sm:px-6">
                  {item.a}
                </p>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}

/* ---------- Marquee ---------- */

export function Marquee({
  items,
  reverse = false,
}: {
  items: string[];
  reverse?: boolean;
}) {
  const [paused, setPaused] = useState(false);

  const Group = (
    <div className="flex shrink-0 items-center gap-10 pr-10" aria-hidden>
      {items.map((item, i) => (
        <span
          key={i}
          className="flex items-center gap-2.5 whitespace-nowrap text-sm font-semibold uppercase tracking-wider text-white/80"
        >
          <span className="h-1.5 w-1.5 rounded-full bg-accent" />
          {item}
        </span>
      ))}
    </div>
  );

  return (
    <div className="group/marquee relative flex items-center gap-2">
      <div
        className="marquee-wrap relative flex-1 overflow-hidden mask-[linear-gradient(90deg,transparent,#000_6%,#000_94%,transparent)]"
        data-paused={paused ? "true" : undefined}
      >
        <div className={`marquee py-1 ${reverse ? "reverse" : ""}`}>
          {Group}
          {Group}
        </div>
        <span className="sr-only">{items.join(", ")}</span>
      </div>
      <button
        type="button"
        onClick={() => setPaused((p) => !p)}
        aria-pressed={paused}
        aria-label={paused ? "Jalankan teks berjalan" : "Jeda teks berjalan"}
        className="shrink-0 rounded-md p-1.5 text-white/70 opacity-0 transition hover:bg-white/10 hover:text-white focus-visible:opacity-100 group-hover/marquee:opacity-100"
      >
        {paused ? (
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <path d="M8 5v14l11-7z" />
          </svg>
        ) : (
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <path d="M6 5h4v14H6zM14 5h4v14h-4z" />
          </svg>
        )}
      </button>
    </div>
  );
}
