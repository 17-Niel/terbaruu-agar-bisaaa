import React, { useState } from "react";
import { Link, router } from "@inertiajs/react";
import LandingLayout from "@/layouts/landing-layout";
import { LowonganDetailDialog } from "./dialogs/lowongan-pekerjaan-detail-dialog";

// Helper
const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString)
        .toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        })
        .replace(/\./g, "/");
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

export default function LandingLowonganPage({
    auth,
    contentData,
    degreeOptions,
    state,
}) {
    const [searchTerm, setSearchTerm] = useState(state.search || "");
    const [selectedDegree, setSelectedDegree] = useState(
        state.degree || "All Degree"
    );
    const [isLowonganOpen, setIsLowonganOpen] = useState(false);
    const [selectedLowongan, setSelectedLowongan] = useState(null);

    const handleSearch = () => {
        router.get(
            route("landing.lowongan"),
            {
                search: searchTerm,
                degree: selectedDegree,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleShowLowongan = (item) => {
        setSelectedLowongan(item);
        setIsLowonganOpen(true);
    };

    return (
        <LandingLayout
            auth={auth}
            activeMenu="lowongan"
            title="Lowongan Pekerjaan - Career Center IT Del"
        >
            {/* Header */}
            <div className="bg-blue-50 py-10 mb-8 border-b border-blue-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-bold text-slate-800 mb-6 text-center md:text-left">
                        Temukan Lowongan Pekerjaan
                    </h2>

                    <div className="bg-white rounded-lg p-4 shadow-sm max-w-4xl">
                        <div className="flex flex-col md:flex-row gap-3">
                            <div className="md:w-48 relative">
                                <select
                                    value={selectedDegree}
                                    onChange={(e) =>
                                        setSelectedDegree(e.target.value)
                                    }
                                    className="w-full appearance-none px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 bg-white cursor-pointer"
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
                            <input
                                type="text"
                                placeholder="Cari posisi atau nama perusahaan..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="flex-1 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none"
                            />
                            <button
                                onClick={handleSearch}
                                className="bg-[#5c8bf5] text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-600 transition shadow-sm"
                            >
                                Cari
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-20">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {contentData.data.length > 0 ? (
                        contentData.data.map((item) => (
                            <div
                                key={item.id_lowongan}
                                className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm hover:shadow-md transition flex items-start gap-4 md:gap-6"
                            >
                                <div className="w-20 h-20 md:w-24 md:h-24 flex-shrink-0 flex items-center justify-center border rounded-lg p-1 bg-white">
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
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-xl font-bold text-[#1F2937] leading-tight mb-1 truncate">
                                        {item.nama_lowongan}
                                    </h3>
                                    <p className="text-[#4B5563] font-medium text-sm mb-3 truncate">
                                        {item.nama_perusahaan}
                                    </p>

                                    {/* --- BAGIAN YANG DIUBAH: STYLING KUNING --- */}
                                    <div className="flex flex-wrap gap-2 mb-4">
                                        {Array.isArray(
                                            item.kualifikasi_pendidikan
                                        ) &&
                                            item.kualifikasi_pendidikan
                                                .slice(0, 3)
                                                .map((edu, idx) => (
                                                    <span
                                                        key={idx}
                                                        className="bg-[#FFF4CC] text-[#8A6D3B] px-3 py-1 rounded-full font-medium text-xs"
                                                    >
                                                        {edu}
                                                    </span>
                                                ))}
                                    </div>
                                    {/* ------------------------------------------ */}

                                    <div className="flex items-center justify-between mt-2">
                                        <div className="text-xs text-gray-500">
                                            <span className="text-red-600 font-semibold">
                                                Deadline:{" "}
                                                {formatDate(item.batas_akhir)}
                                            </span>
                                        </div>
                                        <button
                                            onClick={() =>
                                                handleShowLowongan(item)
                                            }
                                            className="text-blue-600 border border-blue-600 px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-50 transition flex items-center gap-2"
                                        >
                                            <EyeIcon className="w-4 h-4" />{" "}
                                            Detail
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="col-span-full text-center py-20 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <p className="text-gray-500 text-lg">
                                Belum ada lowongan yang tersedia.
                            </p>
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {contentData.links && contentData.links.length > 3 && (
                    <div className="flex justify-center mt-12 gap-2">
                        {contentData.links.map((link, key) =>
                            link.url ? (
                                <Link
                                    key={key}
                                    href={link.url}
                                    className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                                        link.active
                                            ? "bg-blue-600 text-white"
                                            : "bg-white text-gray-600 border hover:bg-gray-50"
                                    }`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={key}
                                    className="px-4 py-2 text-sm text-gray-400"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            )
                        )}
                    </div>
                )}
            </div>

            <LowonganDetailDialog
                openDialog={isLowonganOpen}
                setOpenDialog={setIsLowonganOpen}
                dataDetail={selectedLowongan}
            />
        </LandingLayout>
    );
}
