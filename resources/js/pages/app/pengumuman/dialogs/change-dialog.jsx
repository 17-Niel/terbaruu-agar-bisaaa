import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { route } from "ziggy-js";
import { toast } from "sonner";
// Import TrashIcon juga
import { X, ImageIcon, Trash2 } from "lucide-react";

export default function ChangeDialog({ open, onOpenChange, data }) {
    const {
        data: form,
        setData,
        post,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        id: "",
        judul: "",
        isi: "",
        expired_date: "",
        gambar: null,
        delete_gambar: false, // <--- STATE BARU UNTUK HAPUS GAMBAR
    });

    const getMinDate = () => {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");
        return `${yyyy}-${mm}-${dd}`;
    };

    useEffect(() => {
        if (open) {
            clearErrors();
            if (data) {
                setData({
                    id: data.id,
                    judul: data.judul,
                    isi: data.isi,
                    expired_date: data.expired_date,
                    gambar: null,
                    delete_gambar: false, // Reset status hapus saat buka dialog
                });
            } else {
                reset();
                setData("id", "");
            }
        }
    }, [open, data]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("pengumuman.change"), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
                toast.success(
                    data
                        ? "Pengumuman berhasil diperbarui!"
                        : "Pengumuman berhasil ditambahkan!"
                );
            },
            onError: () => {
                toast.error("Terjadi kesalahan. Silakan periksa inputan Anda.");
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[525px] bg-white p-0 gap-0 overflow-hidden [&>button]:hidden">
                <DialogHeader className="bg-blue-500 px-6 py-4 flex flex-row justify-between items-center">
                    <DialogTitle className="text-xl font-semibold text-white">
                        {data ? "Edit Pengumuman" : "Tambah Pengumuman"}
                    </DialogTitle>
                    <button
                        onClick={() => onOpenChange(false)}
                        className="text-white hover:bg-blue-600 transition-colors"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-5 p-6">
                    <div className="space-y-2">
                        <Label
                            htmlFor="judul"
                            className="text-sm font-medium text-gray-700"
                        >
                            Judul Pengumuman
                        </Label>
                        <Input
                            id="judul"
                            value={form.judul}
                            onChange={(e) => setData("judul", e.target.value)}
                            placeholder="Masukkan judul pengumuman"
                            className="w-full"
                        />
                        {errors.judul && (
                            <span className="text-red-500 text-xs">
                                {errors.judul}
                            </span>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor="isi"
                            className="text-sm font-medium text-gray-700"
                        >
                            Isi Pengumuman
                        </Label>
                        <Textarea
                            id="isi"
                            className="min-h-[100px]"
                            value={form.isi}
                            onChange={(e) => setData("isi", e.target.value)}
                            placeholder="Tulis isi pengumuman di sini..."
                        />
                        {errors.isi && (
                            <span className="text-red-500 text-xs">
                                {errors.isi}
                            </span>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor="expired_date"
                            className="text-sm font-medium text-gray-700"
                        >
                            Expired Date
                        </Label>
                        <Input
                            id="expired_date"
                            type="date"
                            min={getMinDate()}
                            value={form.expired_date}
                            onChange={(e) =>
                                setData("expired_date", e.target.value)
                            }
                            className="w-full"
                        />
                        {errors.expired_date && (
                            <span className="text-red-500 text-xs">
                                {errors.expired_date}
                            </span>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor="gambar"
                            className="text-sm font-medium text-gray-700"
                        >
                            Tambahkan Lampiran (Opsional)
                        </Label>
                        <Input
                            id="gambar"
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                setData((prev) => ({
                                    ...prev,
                                    gambar: e.target.files[0],
                                    delete_gambar: false, // Jika upload baru, batalkan status hapus
                                }));
                            }}
                            className="w-full file:text-blue-600 file:font-semibold"
                        />
                        {errors.gambar && (
                            <span className="text-red-500 text-xs">
                                {errors.gambar}
                            </span>
                        )}

                        {/* --- PREVIEW LOGIC --- */}
                        {/* 1. Tampilkan jika: Ada data gambar di DB, User BELUM upload baru, DAN User TIDAK klik hapus */}
                        {data &&
                            data.gambar_path &&
                            !form.gambar &&
                            !form.delete_gambar && (
                                <div className="mt-4 border-2 border-dashed border-gray-200 rounded-xl p-4 bg-gray-50/50 relative group">
                                    <Label className="text-xs font-semibold text-gray-500 mb-3 flex items-center gap-2">
                                        <ImageIcon className="h-4 w-4" />{" "}
                                        LAMPIRAN SAAT INI
                                    </Label>

                                    <div className="relative w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                        <img
                                            src={`/storage/${data.gambar_path}`}
                                            alt="Preview"
                                            className="w-full h-48 object-contain bg-gray-100"
                                        />

                                        {/* Overlay Nama File */}
                                        <div className="absolute bottom-0 left-0 right-0 bg-black/70 backdrop-blur-sm p-2">
                                            <p className="text-xs text-white truncate text-center font-medium">
                                                {data.gambar_path
                                                    .split("/")
                                                    .pop()}
                                            </p>
                                        </div>

                                        {/* --- TOMBOL HAPUS GAMBAR --- */}
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setData("delete_gambar", true)
                                            }
                                            className="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg transition-transform transform hover:scale-105"
                                            title="Hapus gambar ini"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            )}

                        {/* 2. Pesan jika gambar akan dihapus */}
                        {form.delete_gambar && !form.gambar && (
                            <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
                                <span className="text-sm text-red-600 font-medium flex items-center gap-2">
                                    <Trash2 className="h-4 w-4" /> Gambar akan
                                    dihapus saat disimpan.
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="text-red-600 hover:text-red-700 hover:bg-red-100 h-8 text-xs"
                                    onClick={() =>
                                        setData("delete_gambar", false)
                                    }
                                >
                                    Batal Hapus
                                </Button>
                            </div>
                        )}
                        {/* ----------------------- */}
                    </div>

                    <DialogFooter className="pt-2 gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            className="px-4 border-gray-300"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="px-4 bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-100"
                        >
                            {processing ? "Menyimpan..." : "Simpan Perubahan"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
