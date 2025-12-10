import React from "react";
import { useForm } from "@inertiajs/react";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { route } from "ziggy-js";

export default function DeleteDialog({ open, onOpenChange, data }) {
    const { post, processing } = useForm();

    const handleDelete = () => {
        post(route("pengumuman.delete", { id: data?.id }), {
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {/* 1. p-0: Hapus padding bawaan container.
                2. overflow-hidden: Agar header biru mengikuti rounded corner container.
                3. [&>button]:hidden: Menyembunyikan tombol close (X) bawaan.
            */}
            <DialogContent className="sm:max-w-[450px] p-0 overflow-hidden [&>button]:hidden bg-white gap-0">
                {/* BAGIAN 1: Header Strip Biru Kosong */}
                <div className="w-full h-16 bg-blue-500" />

                {/* BAGIAN 2: Konten Utama (Judul & Deskripsi) */}
                <div className="p-6 pb-2">
                    <DialogHeader className="text-left space-y-3">
                        <DialogTitle className="text-xl font-bold text-gray-900">
                            Apakah Anda Yakin?
                        </DialogTitle>
                        <DialogDescription className="text-gray-500 text-base leading-relaxed">
                            Aksi ini akan menghapus pengumuman tersebut secara
                            permanen
                        </DialogDescription>
                    </DialogHeader>
                </div>

                {/* BAGIAN 3: Footer Tombol */}
                <DialogFooter className="p-6 pt-2 flex flex-row justify-end gap-3 sm:gap-3">
                    {/* Tombol Batal (Outline Abu-abu) */}
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        className="border-gray-300 text-gray-700 hover:bg-gray-50 px-6"
                    >
                        Batal
                    </Button>

                    {/* Tombol Hapus (Outline Merah - sesuai gambar) */}
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleDelete}
                        disabled={processing}
                        className="border-red-300 text-red-500 hover:bg-red-50 hover:border-red-500 hover:text-red-600 px-6 transition-colors"
                    >
                        {processing ? "Menghapus..." : "Hapus"}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
