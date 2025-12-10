import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"; // Pastikan Anda punya komponen Dialog shadcn
import { Separator } from "@/components/ui/separator";
import { IconMapPin, IconWorld } from "@tabler/icons-react";
import dayjs from "dayjs";

export function PerusahaanDetailDialog({
    openDialog,
    setOpenDialog,
    dataDetail,
}) {
    if (!dataDetail) return null;

    return (
        <Dialog open={openDialog} onOpenChange={setOpenDialog}>
            <DialogContent aria-describedby={undefined} className="sm:max-w-[500px] p-0 overflow-hidden gap-0">
                {/* === HEADER BIRU SEPERTI REFERENSI === */}
                <div className="bg-white-500 p-6 text-white">
                    <DialogHeader>
                        <DialogTitle className="text-2xl font-bold text-black">
                            {dataDetail.nama}
                        </DialogTitle>
                        {/* Sub-header / Badges */}
                        <div className="flex flex-wrap gap-2 mt-2">
                            <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-none">
                                {dataDetail.industri || "Industri Tidak Diketahui"}
                            </Badge>
                            {dataDetail.website && (
                                <Badge variant="secondary" className="bg-blue-100 text-blue-800 hover:bg-blue-200 border-none">
                                    <a href={dataDetail.website} target="_blank" rel="noreferrer" className="flex items-center gap-1">
                                        <IconWorld size={12} /> Website
                                    </a>
                                </Badge>
                            )}
                        </div>
                    </DialogHeader>
                </div>

                {/* === BODY CONTENT === */}
                <div className="p-6 max-h-[60vh] overflow-y-auto space-y-5">
                    
                    {/* Logo (Jika ada) */}
                    {dataDetail.url_logo && (
                        <div className="flex justify-center mb-4">
                            <img 
                                src={dataDetail.url_logo} 
                                alt="Logo Perusahaan" 
                                className="h-24 w-24 object-contain border rounded-lg shadow-sm bg-white"
                            />
                        </div>
                    )}

                    {/* Detail Item Helper */}
                    <div className="space-y-1">
                        <h4 className="text-sm font-semibold text-blue-900">Lokasi Perusahaan</h4>
                        <p className="text-sm text-gray-700 flex items-start gap-2">
                             <IconMapPin size={16} className="mt-0.5 text-gray-500" />
                             {dataDetail.lokasi || "-"}
                        </p>
                    </div>

                    <div className="space-y-1">
                        <h4 className="text-sm font-semibold text-blue-900">Industri</h4>
                        <p className="text-sm text-gray-700">
                             {dataDetail.industri || "-"}
                        </p>
                    </div>

                    <div className="space-y-1">
                        <h4 className="text-sm font-semibold text-blue-900">Deskripsi</h4>
                        <p className="text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                            {dataDetail.deskripsi || "-"}
                        </p>
                    </div>
                    
                    <Separator />

                    {/* Footer Info (Created At) */}
                    <div className="text-xs text-gray-500 space-y-1">
                        <p>
                            <span className="font-semibold">Terdaftar:</span>{" "}
                            {dayjs(dataDetail.created_at).format("DD/MM/YYYY | HH:mm")}
                        </p>
                    </div>
                </div>

                <DialogFooter className="p-4 bg-gray-50 border-t">
                    <Button variant="outline" onClick={() => setOpenDialog(false)}>
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
