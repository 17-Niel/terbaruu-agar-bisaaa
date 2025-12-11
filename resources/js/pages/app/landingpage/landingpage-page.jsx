import React, { useState, useEffect } from "react";
import { router, Link } from "@inertiajs/react";
import LandingLayout from "@/layouts/landing-layout";

// --- IMPORT COMPONENT DIALOG ---
import { LowonganDetailDialog } from "./dialogs/lowongan-pekerjaan-detail-dialog";
import { CampusHiringDetailDialog } from "./dialogs/campus-hiring-detail-dialog";
import { PerusahaanDetailDialog } from "./dialogs/perusahaan-detail-dialog";
import { PengumumanDetailDialog } from "./dialogs/pengumuman-detail-dialog";
// Helper Format Tanggal
const formatDate = (dateString, includeTime = false) => {
    if (!dateString) return "-";
    const date = new Date(dateString);

    // Format Tanggal (DD/MM/YYYY)
    const dateOptions = { day: "2-digit", month: "2-digit", year: "numeric" };
    let result = date
        .toLocaleDateString("id-ID", dateOptions)
        .replace(/\./g, "/");

    // Gunakan parameter 'includeTime'
    if (includeTime) {
        const timeOptions = {
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
        };
        // Format Waktu (HH:mm), replace titik dengan titik dua agar standar
        const time = date
            .toLocaleTimeString("id-ID", timeOptions)
            .replace(".", ":");
        result += ` ${time}`;
    }

    return result;
};

const EyeIcon = ({ className }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        strokeWidth={1.5}
        stroke="currentColor"
        className={className}
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
        />
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
        />
    </svg>
);

export default function LandingPage({
    auth,
    banners,
    contentData,
    degreeOptions,
    state,
}) {
    // State Lokal
    const [searchTerm, setSearchTerm] = useState(state.search || "");
    const [selectedDegree, setSelectedDegree] = useState(
        state.degree || "All Degree"
    );
    const [currentBannerIndex, setCurrentBannerIndex] = useState(0);

    // State Dialog
    const [isLowonganOpen, setIsLowonganOpen] = useState(false);
    const [selectedLowongan, setSelectedLowongan] = useState(null);
    const [isCampusOpen, setIsCampusOpen] = useState(false);
    const [selectedCampus, setSelectedCampus] = useState(null);
    const [isCompanyOpen, setIsCompanyOpen] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState(null);

    // --- [BARU] STATE PENGUMUMAN ---
    const [isPengumumanOpen, setIsPengumumanOpen] = useState(false);
    const [selectedPengumuman, setSelectedPengumuman] = useState(null);
    const handleShowPengumuman = (item) => {
        setSelectedPengumuman(item);
        setIsPengumumanOpen(true);
    };

    // Handler Dialog
    const handleShowLowongan = (item) => {
        setSelectedLowongan(item);
        setIsLowonganOpen(true);
    };
    const handleShowCampus = (item) => {
        setSelectedCampus(item);
        setIsCampusOpen(true);
    };
    const handleShowCompany = (item) => {
        setSelectedCompany(item);
        setIsCompanyOpen(true);
    };

    // --- FIX: Definisi fungsi dipindahkan ke SINI (Sebelum useEffect) ---
    const nextBanner = () =>
        setCurrentBannerIndex((prev) =>
            prev === banners.length - 1 ? 0 : prev + 1
        );
    const prevBanner = () =>
        setCurrentBannerIndex((prev) =>
            prev === 0 ? banners.length - 1 : prev - 1
        );

    // Auto Play Banner
    useEffect(() => {
        if (banners.length <= 1) return;
        const interval = setInterval(() => {
            nextBanner();
        }, 5000);
        return () => clearInterval(interval);
    }, [banners.length]); // Dependency cukup banners.length agar tidak reset setiap render

    // Sync URL State
    useEffect(() => {
        setSearchTerm(state.search || "");
        setSelectedDegree(state.degree || "All Degree");
    }, [state]);

    // Navigasi & Filter
    const handleSearch = () => {
        router.get(
            route("landing.index"),
            {
                tab: state.activeTab,
                search: searchTerm,
                degree: selectedDegree,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ["contentData", "state"],
            }
        );
    };

    const handleTabChange = (tabName) => {
        router.get(
            route("landing.index"),
            {
                tab: tabName,
                search: "",
                degree: "All Degree",
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ["contentData", "state"],
            }
        );
    };

    const getMoreButtonLabel = () => {
        if (state.activeTab === "perusahaan") return "Lihat Semua Perusahaan";
        if (state.activeTab === "campus-hiring")
            return "Lihat Semua Campus Hiring";
        return "Lihat Semua Lowongan";
    };

    // Helper Link "Lihat Semua" mengarah ke halaman baru
    const getMoreButtonRoute = () => {
        if (state.activeTab === "perusahaan")
            return route("landing.perusahaan");
        if (state.activeTab === "campus-hiring")
            return route("landing.campus-hiring");
        return route("landing.lowongan");
    };

    // --- [BARU] LOGIKA TAMPILAN PENGUMUMAN ---
    if (state.activeTab === "pengumuman") {
        return (
            <div
                key={item.id_pengumuman || item.id}
                className="bg-white border-l-4 border-orange-400 rounded-xl p-8 shadow-sm hover:shadow-md transition flex flex-col"
            >
                <div className="flex justify-between items-start mb-4">
                    <span className="text-orange-600 font-bold text-xs tracking-wide bg-orange-50 px-2 py-1 rounded">
                        PENGUMUMAN
                    </span>
                    <span className="text-gray-400 text-xs">
                        {formatDate(item.created_at)}
                    </span>
                </div>
                <h3
                    className="text-xl font-bold text-gray-900 mb-3 line-clamp-2 cursor-pointer hover:text-blue-600"
                    onClick={() => handleShowPengumuman(item)}
                >
                    {item.judul}
                </h3>
                <p className="text-gray-500 mb-6 line-clamp-3 text-sm flex-1">
                    {item.konten}
                </p>
                <button
                    onClick={() => handleShowPengumuman(item)}
                    className="self-start text-white bg-orange-500 hover:bg-orange-600 px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm"
                >
                    Baca Detail
                </button>
            </div>
        );
    }

    const isJobTab =
        state.activeTab === "lowongan" || state.activeTab === "campus-hiring";
    const getSearchPlaceholder = () => {
        if (state.activeTab === "perusahaan") return "Masukkan Nama Perusahaan";
        if (state.activeTab === "campus-hiring")
            return "Masukkan Judul Campus Hiring";
        return "Masukkan Judul Lowongan Kerja";
    };

    return (
        <LandingLayout auth={auth} activeMenu="home">
            {/* --- HERO / BANNER --- */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {banners.length > 0 ? (
                    <div className="relative w-full aspect-[21/9] bg-blue-100 rounded-2xl overflow-hidden shadow-sm group">
                        <div
                            className="flex h-full transition-transform duration-500 ease-out"
                            style={{
                                transform: `translateX(-${
                                    currentBannerIndex * 100
                                }%)`,
                            }}
                        >
                            {banners.map((banner, index) => (
                                <div
                                    key={banner.id_banner || index}
                                    className="min-w-full h-full relative"
                                >
                                    <img
                                        src={banner.url_gambar}
                                        alt={banner.nama_banner}
                                        className="w-full h-full object-cover"
                                    />
                                </div>
                            ))}
                        </div>
                        {banners.length > 1 && (
                            <>
                                <button
                                    onClick={prevBanner}
                                    className="absolute left-0 top-0 bottom-0 w-16 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-gradient-to-r from-black/20 text-white"
                                >
                                    <svg
                                        className="w-8 h-8"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M15 19l-7-7 7-7"
                                        />
                                    </svg>
                                </button>
                                <button
                                    onClick={nextBanner}
                                    className="absolute right-0 top-0 bottom-0 w-16 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-gradient-to-l from-black/20 text-white"
                                >
                                    <svg
                                        className="w-8 h-8"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </button>
                            </>
                        )}
                        <div className="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-10">
                            {banners.map((_, idx) => (
                                <button
                                    key={idx}
                                    onClick={() => setCurrentBannerIndex(idx)}
                                    className={`w-2 h-2 rounded-full transition-all ${
                                        idx === currentBannerIndex
                                            ? "bg-white w-6"
                                            : "bg-white/50"
                                    }`}
                                />
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="w-full h-64 bg-blue-400 rounded-2xl flex items-center justify-center text-white">
                        <span className="text-lg font-semibold">
                            Selamat Datang di Career Center IT Del
                        </span>
                    </div>
                )}
                <div className="text-center mt-8 mb-6">
                    <h2 className="text-2xl font-bold text-gray-900">
                        Temukan Karir Anda Di sini !
                    </h2>
                </div>
            </div>

            {/* --- SEARCH BOX --- */}
            <div className="max-w-5xl mx-auto px-4 mb-10">
                <div className="bg-[#5c8bf5] rounded-lg p-6 shadow-lg">
                    <div className="flex flex-col md:flex-row gap-3">
                        {isJobTab && (
                            <div className="relative md:w-48">
                                <select
                                    value={selectedDegree}
                                    onChange={(e) =>
                                        setSelectedDegree(e.target.value)
                                    }
                                    className="w-full appearance-none px-4 py-3 rounded-lg border-none focus:ring-0 text-gray-800 font-medium bg-white cursor-pointer"
                                >
                                    <option value="All Degree">
                                        All Degree
                                    </option>
                                    {degreeOptions.map((deg) => (
                                        <option key={deg} value={deg}>
                                            {deg}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <input
                            type="text"
                            placeholder={getSearchPlaceholder()}
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="flex-1 px-4 py-3 rounded-lg border-none focus:ring-0 placeholder-gray-400 text-gray-700 bg-white"
                        />
                        <button
                            onClick={handleSearch}
                            className="bg-white text-[#5c8bf5] px-10 py-3 rounded-lg font-bold hover:bg-gray-100 transition shadow-sm"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </div>

            {/* --- TABS & CONTENT --- */}
            <div className="max-w-5xl mx-auto px-4">
                <div className="flex gap-8 border-b border-gray-200 mb-8 overflow-x-auto">
                    {[
                        { id: "lowongan", label: "Lowongan Pekerjaan" },
                        { id: "campus-hiring", label: "Campus Hiring" },
                        { id: "perusahaan", label: "Perusahaan" },
                        { id: "pengumuman", label: "Pengumuman" }, // <--- [BARU] Tab Ditambahkan
                    ].map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => handleTabChange(tab.id)}
                            className={`pb-3 text-lg font-bold whitespace-nowrap transition-colors border-b-4 ${
                                state.activeTab === tab.id
                                    ? "border-blue-500 text-blue-600"
                                    : "border-transparent text-gray-400 hover:text-gray-600"
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    {contentData.data.length > 0 ? (
                        contentData.data.slice(0, 6).map((item) => {
                            if (state.activeTab === "perusahaan") {
                                return (
                                    <div
                                        key={item.id_perusahaan}
                                        className="bg-white border border-gray-100 rounded-xl p-8 shadow-sm hover:shadow-md transition flex items-start gap-6"
                                    >
                                        <div className="w-28 h-28 flex-shrink-0 flex items-center justify-center">
                                            <img
                                                src={
                                                    item.url_logo ||
                                                    "/images/default-company.png"
                                                }
                                                alt={item.nama}
                                                className="max-w-full max-h-full object-contain"
                                                onError={(e) =>
                                                    (e.target.src =
                                                        "https://ui-avatars.com/api/?name=" +
                                                        item.nama)
                                                }
                                            />
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="text-2xl font-bold text-[#1F2937] mb-2">
                                                {item.nama}
                                            </h3>
                                            <span className="inline-block bg-[#FFF4CC] text-[#8A6D3B] text-sm px-4 py-1.5 rounded-full font-medium mb-6">
                                                {item.total_jobs} Lowongan Kerja
                                                Tersedia
                                            </span>
                                            <div>
                                                <button
                                                    onClick={() =>
                                                        handleShowCompany(item)
                                                    }
                                                    className="flex items-center gap-2 text-blue-600 border border-blue-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition"
                                                >
                                                    <EyeIcon className="w-5 h-5" />{" "}
                                                    Detail
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            } else {
                                const title =
                                    item.nama_lowongan ||
                                    item.nama_campus_hiring;
                                const isCampus =
                                    state.activeTab === "campus-hiring";
                                return (
                                    <div
                                        key={
                                            item.id_lowongan ||
                                            item.id_campus_hiring
                                        }
                                        className="bg-white border border-gray-100 rounded-xl p-8 shadow-sm hover:shadow-md transition relative"
                                    >
                                        {isCampus && (
                                            <span className="absolute top-0 right-0 bg-blue-600 text-white text-[10px] px-3 py-1.5 rounded-bl-lg font-bold uppercase tracking-wider">
                                                CAMPUS HIRING
                                            </span>
                                        )}
                                        <div className="flex items-start gap-6">
                                            <div className="w-28 h-28 flex-shrink-0 flex items-center justify-center">
                                                <img
                                                    src={item.url_logo}
                                                    alt={item.nama_perusahaan}
                                                    className="max-w-full max-h-full object-contain"
                                                    onError={(e) =>
                                                        (e.target.src =
                                                            "https://ui-avatars.com/api/?name=" +
                                                            item.nama_perusahaan)
                                                    }
                                                />
                                            </div>
                                            <div className="flex-1">
                                                <h3 className="text-2xl font-bold text-[#1F2937] leading-tight mb-2">
                                                    {title}
                                                </h3>
                                                <div className="flex flex-wrap gap-2 mb-2 text-sm items-center">
                                                    <span className="text-gray-500">
                                                        Looking for
                                                    </span>
                                                    {Array.isArray(
                                                        item.kualifikasi_pendidikan
                                                    ) &&
                                                        item.kualifikasi_pendidikan.map(
                                                            (edu, idx) => (
                                                                <span
                                                                    key={idx}
                                                                    className="bg-[#FFF4CC] text-[#8A6D3B] px-3 py-1 rounded-full font-medium"
                                                                >
                                                                    {edu}
                                                                </span>
                                                            )
                                                        )}
                                                </div>
                                                <p className="text-[#4B5563] font-medium text-base mb-4">
                                                    {item.nama_perusahaan}
                                                </p>
                                                <div className="text-sm space-y-1 mb-6">
                                                    <div className="text-[#1F2937] font-medium">
                                                        Posted{" "}
                                                        <span className="font-bold">
                                                            {formatDate(
                                                                item.created_at,
                                                                true
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="text-[#DC2626] font-bold">
                                                        Deadline{" "}
                                                        {formatDate(
                                                            item.batas_akhir,
                                                            true
                                                        )}
                                                    </div>
                                                </div>
                                                <button
                                                    onClick={() => {
                                                        if (isCampus)
                                                            handleShowCampus(
                                                                item
                                                            );
                                                        else
                                                            handleShowLowongan(
                                                                item
                                                            );
                                                    }}
                                                    className="flex items-center gap-2 text-blue-600 border border-blue-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition"
                                                >
                                                    <EyeIcon className="w-5 h-5" />{" "}
                                                    Detail
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            }
                        })
                    ) : (
                        <div className="col-span-1 md:col-span-2 text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <p className="text-gray-500">
                                Belum ada data yang tersedia untuk kategori ini.
                            </p>
                        </div>
                    )}
                </div>

                {/* --- BUTTON LIHAT SEMUA (Mengarah ke Halaman Baru) --- */}
                <div className="flex justify-center mb-16">
                    <Link
                        href={getMoreButtonRoute()}
                        className="bg-[#5885E6] hover:bg-blue-600 text-white font-bold text-lg py-4 px-12 rounded-full shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1"
                    >
                        {getMoreButtonLabel()}
                    </Link>
                </div>
            </div>

            {/* --- DIALOGS --- */}
            <LowonganDetailDialog
                openDialog={isLowonganOpen}
                setOpenDialog={setIsLowonganOpen}
                dataDetail={selectedLowongan}
            />
            <CampusHiringDetailDialog
                openDialog={isCampusOpen}
                setOpenDialog={setIsCampusOpen}
                dataDetail={selectedCampus}
                auth={auth}
            />
            <PerusahaanDetailDialog
                openDialog={isCompanyOpen}
                setOpenDialog={setIsCompanyOpen}
                dataDetail={selectedCompany}
            />

            <PengumumanDetailDialog
                openDialog={isPengumumanOpen}
                setOpenDialog={setIsPengumumanOpen}
                dataDetail={selectedPengumuman}
            />
        </LandingLayout>
    );
}
