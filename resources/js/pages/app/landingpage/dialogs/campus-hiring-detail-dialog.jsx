import React, { useState } from 'react';
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogTitle } from "@/components/ui/dialog";
import { Separator } from "@/components/ui/separator";
import { DaftarDetailDialog } from "./daftar-detail"; 
import { toast } from "sonner"; 
import dayjs from "dayjs";
import "dayjs/locale/id";

export function CampusHiringDetailDialog({ openDialog, setOpenDialog, dataDetail, auth }) {
    // 1. PINDAHKAN HOOKS KE PALING ATAS (SEBELUM RETURN APAPUN)
    const [isDaftarDialogOpen, setIsDaftarDialogOpen] = useState(false);

    // 2. BARU LAKUKAN PENGECEKAN KONDISIONAL
    if (!dataDetail) return null;

    // --- Sisa logika di bawah ini aman karena dijalankan setelah hook & null check ---

    // Helper convert GMT+0 → GMT+7
    const toGMT7 = (date) => dayjs(date).add(7, "hour");

    // Helper cek expired
    const isExpired = (dateString) => {
        if (!dateString) return false;
        const deadline = new Date(dateString);
        const today = new Date();
        today.setHours(0, 0, 0, 0); 
        return today > deadline;
    };

    const expired = isExpired(dataDetail.batas_akhir);

    // Helper parsing pendidikan
    let educationBadges = [];
    if (Array.isArray(dataDetail.kualifikasi_pendidikan)) {
        educationBadges = dataDetail.kualifikasi_pendidikan;
    } else if (typeof dataDetail.kualifikasi_pendidikan === "string") {
        try {
            const parsed = JSON.parse(dataDetail.kualifikasi_pendidikan);
            educationBadges = Array.isArray(parsed) ? parsed : [dataDetail.kualifikasi_pendidikan];
        } catch {
            educationBadges = [dataDetail.kualifikasi_pendidikan];
        }
    } else {
        educationBadges = ["-"];
    }

    const handleDaftarClick = () => {
        if (!auth) {
            window.location.href = route('auth.login');
            return;
        }
        if (auth.alias === 'Alumni') { 
             setIsDaftarDialogOpen(true);
        } else {
             toast.error("Akun Anda tidak cocok. Anda TIDAK BISA mendaftar.");
        }
    };

    return (
        <>
            <Dialog open={openDialog} onOpenChange={setOpenDialog}>
                <DialogContent className="sm:max-w-[550px] bg-white p-0 gap-0 overflow-hidden border shadow-lg rounded-xl">

                    {/* Bagian Konten Scrollable */}
                    <div className="p-6 pb-4 overflow-y-auto max-h-[70vh]">
                        
                        {/* Header Judul */}
                        <div className="mb-6">
                            <DialogTitle className="text-2xl font-bold text-blue-950 mb-1">
                                {dataDetail.nama_campus_hiring || "Nama Campus Hiring"}
                            </DialogTitle>
                            <p className="text-blue-600 font-medium mb-3 text-sm">
                                {dataDetail.nama_perusahaan}
                            </p>

                            {/* Looking For */}
                            <div className="flex items-center gap-2 text-sm text-slate-500">
                                <span>Looking for</span>
                                <div className="flex flex-wrap gap-1">
                                    {educationBadges.map((item, index) => (
                                        <Badge
                                            key={index}
                                            variant="secondary"
                                            className="bg-[#FEF9C3] text-yellow-800 hover:bg-[#FEF9C3] rounded-md px-3 font-normal"
                                        >
                                            {item}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Detail */}
                        <div className="space-y-5">
                            <DetailItem label="Nama Perusahaan" value={dataDetail.nama_perusahaan} />
                            <DetailItem label="Departemen" value={dataDetail.departemen} />
                            <DetailItem label="Lokasi" value={dataDetail.lokasi} />
                            <DetailItem label="Deskripsi" value={dataDetail.deskripsi} />
                            <DetailItem label="Kualifikasi" value={dataDetail.kualifikasi} />
                            <DetailItem label="Benefit" value={dataDetail.benefit} />
                        </div>
                    </div>

                    {/* Footer Section */}
                    <div className="mt-2">
                        <Separator />
                        <div className="p-6 flex items-end justify-between bg-white">

                            {/* Info Tanggal Kiri */}
                            <div className="text-sm space-y-1">
                                <p className="text-blue-900 font-medium">
                                    Posted {toGMT7(dataDetail.created_at).format("DD/MM/YYYY")} |{" "}
                                    {toGMT7(dataDetail.created_at).format("HH:mm")}
                                </p>
                                <p className="text-red-600 font-bold">
                                    Deadline{" "}
                                    {dataDetail.batas_akhir
                                        ? toGMT7(dataDetail.batas_akhir).format("DD/MM/YYYY")
                                        : "-"}
                                    {expired && " (Ditutup)"}
                                </p>
                            </div>

                            {/* Tombol Kanan */}
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    className="border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold px-4"
                                    onClick={() => setOpenDialog(false)}
                                >
                                    Tutup
                                </Button>
                                <Button 
                                    onClick={handleDaftarClick}
                                    disabled={expired}
                                    className={`${expired ? 'bg-gray-400 cursor-not-allowed hover:bg-gray-400' : 'bg-blue-600 hover:bg-blue-700'} text-white font-semibold px-4`}
                                >
                                    {expired ? 'Ditutup' : 'Daftar'}
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <DaftarDetailDialog 
                openDialog={isDaftarDialogOpen} 
                setOpenDialog={setIsDaftarDialogOpen} 
                dataDetail={dataDetail}
                auth={auth} 
            />
        </>
    );
}

function DetailItem({ label, value }) {
    const displayValue = value || "-";
    return (
        <div className="flex flex-col gap-1">
            <h4 className="text-blue-950 font-bold text-sm">{label}</h4>
            <div className="text-slate-600 text-sm leading-relaxed whitespace-pre-line">
                {displayValue}
            </div>
        </div>
    );
}