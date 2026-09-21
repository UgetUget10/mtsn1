/** Sub-halaman per kelompok menu. Sumber tunggal untuk hub, sub-halaman, dan navigasi. */

export type SectionNavItem = { label: string; href: string; external?: boolean };

export const akademikMenu: SectionNavItem[] = [
  { label: "Ikhtisar", href: "/akademik" },
  { label: "Kurikulum", href: "/akademik/kurikulum" },
  { label: "Proses Pembelajaran", href: "/akademik/pembelajaran" },
  { label: "Penilaian & Rapor", href: "/akademik/penilaian" },
  { label: "Bimbingan Konseling", href: "/akademik/bk" },
  { label: "Program Unggulan", href: "/akademik/program-unggulan" },
  { label: "Aplikasi Digital", href: "/akademik/aplikasi" },
];

export const layananMenu: SectionNavItem[] = [
  { label: "Ikhtisar", href: "/layanan" },
  { label: "Standar Layanan", href: "/layanan/standar" },
  { label: "SOP PTSP", href: "/layanan/sop" },
  { label: "Maklumat Pelayanan", href: "/layanan/maklumat" },
  { label: "Survei Kepuasan", href: "/layanan/survei" },
  { label: "Pengaduan", href: "/layanan/pengaduan" },
  { label: "E-Repository", href: "/dokumen" },
];

export const ziMenu: SectionNavItem[] = [
  { label: "Ikhtisar", href: "/area-zi" },
  { label: "Menuju WBK / WBBM", href: "/area-zi/wbk" },
  { label: "Pengendalian Gratifikasi", href: "/area-zi/gratifikasi" },
  { label: "Whistleblowing (WBS)", href: "/area-zi/wbs" },
  { label: "LHKPN & LHKASN", href: "/area-zi/lhkpn" },
];

export const ppdbMenu: SectionNavItem[] = [
  { label: "Ikhtisar", href: "/ppdb" },
  { label: "Alur Pendaftaran", href: "/ppdb/alur" },
  { label: "Berkas Persyaratan", href: "/ppdb/berkas" },
  { label: "Jadwal & Pengumuman", href: "/ppdb/jadwal" },
];
