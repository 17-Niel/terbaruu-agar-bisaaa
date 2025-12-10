import React from "react";
import { router } from "@inertiajs/react";

export function ArtikelDeleteDialog({ open, onOpenChange, data }) {
    if (!open) return null;

    const handleDelete = () => {
        const payload = { artikelIds: data.artikelIds };
        router.post("/artikel/delete", payload, {
            onSuccess: () => {
                onOpenChange(false);
                router.visit("/artikel", {
                    preserveState: false,
                    only: ["artikelList"],
                });
            },
            onError: () => {
                alert("Gagal menghapus.");
            },
        });
    };

    return (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div className="bg-white rounded p-6 w-full max-w-md">
                <h3 className="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <div className="mb-4">
                    Apakah Anda yakin ingin menghapus{" "}
                    {data.dataList?.length || 0} artikel?
                </div>
                <div className="flex gap-2 justify-end">
                    <button
                        onClick={() => onOpenChange(false)}
                        className="px-3 py-1 border rounded"
                    >
                        Batal
                    </button>
                    <button
                        onClick={handleDelete}
                        className="px-3 py-1 bg-red-600 text-white rounded"
                    >
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    );
}
