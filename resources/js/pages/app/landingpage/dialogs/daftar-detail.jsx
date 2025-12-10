import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useForm } from "@inertiajs/react";
import React, { useEffect } from "react";
import { toast } from "sonner";

export function DaftarDetailDialog({ openDialog, setOpenDialog, dataDetail, auth }) {
    // 1. PINDAHKAN HOOKS KE ATAS SEBELUM RETURN
    // Gunakan optional chaining (?.) dan '|| ""' agar tidak error saat dataDetail null di awal
    const { data, setData, post, processing, errors, reset } = useForm({
        id_campus_hiring: dataDetail?.id_campus_hiring || "",
        nama_pelamar: auth?.nama || "", 
        url_cv: "",
    });

    useEffect(() => {
        // Update data form hanya jika dialog terbuka dan dataDetail tersedia
        if (openDialog && dataDetail) {
            setData({
                id_campus_hiring: dataDetail.id_campus_hiring,
                nama_pelamar: auth?.nama || "",
                url_cv: "",
            });
        }
    }, [openDialog, dataDetail, auth]);

    // 2. BARU LAKUKAN PENGECEKAN NULL
    if (!dataDetail) return null;

    const handleSubmit = (e) => {
        e.preventDefault();
        
        post(route('landing.lamar-campus-hiring'), {
            onSuccess: () => {
                // toast.success("Berhasil mendaftar!");
                setOpenDialog(false);
                reset();
            },
            // 3. HAPUS PARAMETER 'err' YANG TIDAK DIPAKAI
            onError: () => {
                toast.error("Gagal mendaftar, periksa inputan Anda.");
            }
        });
    };

    return (
        <Dialog open={openDialog} onOpenChange={setOpenDialog}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Daftar: {dataDetail.nama_campus_hiring}</DialogTitle>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4 py-4">
                    {/* INPUT NAMA PELAMAR */}
                    <div className="space-y-2">
                        <Label htmlFor="nama_pelamar">Nama Lengkap</Label>
                        <Input
                            id="nama_pelamar"
                            placeholder="Masukkan nama lengkap Anda"
                            value={data.nama_pelamar}
                            onChange={(e) => setData("nama_pelamar", e.target.value)}
                            disabled={processing}
                        />
                        {errors.nama_pelamar && (
                            <span className="text-red-500 text-xs">{errors.nama_pelamar}</span>
                        )}
                    </div>

                    {/* INPUT LINK CV */}
                    <div className="space-y-2">
                        <Label htmlFor="url_cv">Link CV / Portfolio</Label>
                        <Input
                            id="url_cv"
                            placeholder="https://linkedin.com/in/username atau Google Drive Link"
                            value={data.url_cv}
                            onChange={(e) => setData("url_cv", e.target.value)}
                            disabled={processing}
                        />
                        {errors.url_cv && (
                            <span className="text-red-500 text-xs">{errors.url_cv}</span>
                        )}
                    </div>

                    <DialogFooter>
                        <Button 
                            type="button" 
                            variant="secondary" 
                            onClick={() => setOpenDialog(false)}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Mengirim..." : "Kirim Lamaran"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}