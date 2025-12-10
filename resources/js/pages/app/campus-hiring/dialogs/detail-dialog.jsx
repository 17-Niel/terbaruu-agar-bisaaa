import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from "@/components/ui/dialog";
import { Separator } from "@/components/ui/separator";
import dayjs from "dayjs";
import "dayjs/locale/id";

export function CampusHiringDetailDialog({ openDialog, setOpenDialog, dataDetail }) {
    if (!dataDetail) return null;

    // Helper convert GMT+0 → GMT+7
    const toGMT7 = (date) => dayjs(date).add(7, "hour");

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

    return (
        <Dialog open={openDialog} onOpenChange={setOpenDialog}>
            <DialogContent className="sm:max-w-[550px] bg-white p-0 gap-0 overflow-hidden border shadow-lg rounded-xl">

                {/* Bagian Konten Scrollable */}
                <div className="p-6 pb-4 overflow-y-auto max-h-[70vh]">
                    
                    {/* Header Judul */}
                    <div className="mb-6">
                        <DialogTitle className="text-2xl font-bold text-blue-950 mb-2">
                            {dataDetail.nama_campus_hiring || "Nama Campus Hiring"}
                        </DialogTitle>

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
                        <DetailItem label="Jenis Lowongan" value={dataDetail.jenis_lowongan} />
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
                            {/* Tanggal Publish (tetap pakai jam) */}
                            <p className="text-blue-900 font-medium">
                                Posted {toGMT7(dataDetail.created_at).format("DD/MM/YYYY")} |{" "}
                                {toGMT7(dataDetail.created_at).format("HH:mm")}
                            </p>

                            {/* Deadline tanpa jam */}
                            <p className="text-red-600 font-bold">
                                Deadline{" "}
                                {dataDetail.batas_akhir
                                    ? toGMT7(dataDetail.batas_akhir).format("DD/MM/YYYY")
                                    : "-"}
                            </p>
                        </div>

                        {/* Tombol */}
                        <Button
                            variant="outline"
                            className="border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold px-6"
                            onClick={() => setOpenDialog(false)}
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
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