import React from "react";
import AppLayout from "@/layouts/app-layout";
import { router, usePage } from "@inertiajs/react";
import * as Icon from "@tabler/icons-react";

// PENTING: Nama function HARUS huruf besar di awal (PascalCase)
export default function ArtikelDetailPage() {
    const { artikel = {} } = usePage().props;

    // Helper untuk cek tipe file berdasarkan ekstensi URL
    const getFileType = (url) => {
        if (!url) return null;
        const extension = url.split(".").pop().toLowerCase();
        if (["pdf"].includes(extension)) return "image";
        if (extension === "pdf") return "pdf";
        return "other";
    };

    const fileType = getFileType(artikel.attachment_url);

    // Format Tanggal (Opsional, jika ada created_at)
    const formattedDate = artikel.created_at
        ? new Date(artikel.created_at).toLocaleDateString("id-ID", {
              day: "numeric",
              month: "long",
              year: "numeric",
          })
        : null;

    return (
        <AppLayout>
            <div className="min-h-screen bg-muted/50 py-10">
                <div className="max-w-4xl mx-auto px-4 sm:px-6">
                    {/* Tombol Kembali */}
                    <button
                        onClick={() => router.visit("/artikel")}
                        className="group flex items-center gap-2 text-gray-500 hover:text-primary mb-6 transition-colors duration-200"
                    >
                        <div className="p-2 bg-card rounded-full shadow-sm group-hover:shadow-md border border-gray-100 transition-all">
                            <Icon.IconArrowLeft size={18} />
                        </div>
                        <span className="font-medium text-sm">
                            Kembali ke Daftar
                        </span>
                    </button>

                    {/* Kontainer Utama */}
                    <div className="bg-card rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        {/* Header: Judul & Meta */}
                        <div className="p-8 border-b border-gray-100">
                            <div className="flex flex-wrap items-center gap-3 mb-4">
                                {/* Badge Kategori */}
                                <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-100">
                                    <Icon.IconTag size={14} className="mr-1" />
                                    {artikel.category || "Tanpa Kategori"}
                                </span>

                                {/* Tanggal (Jika ada) */}
                                {formattedDate && (
                                    <span className="inline-flex items-center text-gray-400 text-xs">
                                        <Icon.IconCalendar
                                            size={14}
                                            className="mr-1"
                                        />
                                        {formattedDate}
                                    </span>
                                )}
                            </div>

                            <h1 className="text-3xl md:text-4xl font-bold text-foreground leading-tight">
                                {artikel.title}
                            </h1>
                        </div>

                        {/* Body: Deskripsi */}
                        <div className="p-8">
                            <div className="prose max-w-none text-muted-foreground leading-relaxed whitespace-pre-line">
                                {artikel.content}
                            </div>
                        </div>

                        {/* Footer: Lampiran / Attachment */}
                        {artikel.attachment_url && (
                            <div className="bg-muted p-8 border-t border-gray-100">
                                <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
                                    <Icon.IconPaperclip size={18} />
                                    Lampiran File
                                </h3>

                                <div className="border border-gray-200 rounded-xl bg-card p-4 shadow-sm">
                                    {/* LOGIKA PREVIEW */}

                                    {/* 1. Jika Gambar */}
                                    {fileType === "image" && (
                                        <div className="mb-4 rounded-lg overflow-hidden border border-gray-100">
                                            <img
                                                src={artikel.attachment_url}
                                                alt="Lampiran"
                                                className="w-full h-auto max-h-[500px] object-contain bg-gray-100"
                                            />
                                        </div>
                                    )}

                                    {/* 2. Jika PDF */}
                                    {fileType === "pdf" && (
                                        <div className="mb-4 rounded-lg overflow-hidden border border-gray-100 h-[500px]">
                                            <iframe
                                                src={artikel.attachment_url}
                                                className="w-full h-full"
                                                title="Preview PDF"
                                            ></iframe>
                                        </div>
                                    )}

                                    {/* Tombol Download / Buka Full */}
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="p-3 bg-primary/10 text-primary rounded-lg">
                                                {fileType === "image" ? (
                                                    <Icon.IconPhoto size={24} />
                                                ) : fileType === "pdf" ? (
                                                    <Icon.IconFileTypePdf
                                                        size={24}
                                                    />
                                                ) : (
                                                    <Icon.IconFile size={24} />
                                                )}
                                            </div>
                                            <div className="text-sm">
                                                <p className="font-medium text-foreground">
                                                    File Lampiran
                                                </p>
                                                <p className="text-gray-500 text-xs">
                                                    Klik tombol untuk mengunduh
                                                </p>
                                            </div>
                                        </div>

                                        <button
                                            onClick={() =>
                                                window.open(
                                                    artikel.attachment_url,
                                                    "_blank",
                                                    "noopener,noreferrer"
                                                )
                                            }
                                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-white font-medium hover:bg-primary/80 transition-colors shadow-blue-200 shadow-lg"
                                        >
                                            <Icon.IconDownload size={18} />
                                            {fileType === "pdf" ||
                                            fileType === "image"
                                                ? "Buka Full Size"
                                                : "Download File"}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
