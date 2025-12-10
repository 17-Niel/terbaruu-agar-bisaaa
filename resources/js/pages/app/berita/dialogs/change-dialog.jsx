import React, { useEffect, useRef } from "react";
// IMPORT ASLI (JANGAN DIKOMENTAR)
import { useForm } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { X, Upload, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { route } from "ziggy-js"; // IMPORT ASLI

// Import Trix Library (Pastikan sudah npm install trix)
import "trix";
import "trix/dist/trix.css";

// Komponen Trix Editor (Real Implementation)
const TrixEditor = ({ value, onChange, placeholder }) => {
    const trixRef = useRef(null);

    useEffect(() => {
        const trixElement = trixRef.current;
        if (!trixElement) return;

        // Load data awal jika ada
        if (
            value &&
            trixElement.editor &&
            !trixElement.editor.getDocument().toString()
        ) {
            trixElement.editor.loadHTML(value);
        }

        const handleChange = (e) => {
            onChange(e.target.value);
        };

        trixElement.addEventListener("trix-change", handleChange);
        return () =>
            trixElement.removeEventListener("trix-change", handleChange);
    }, []);

    // Sinkronisasi jika value berubah dari luar (misal reset form)
    useEffect(() => {
        const trixElement = trixRef.current;
        if (
            trixElement &&
            value !== undefined &&
            trixElement.innerHTML !== value
        ) {
            // Cek agar tidak overwrite saat user mengetik
            if (
                trixElement.editor.getDocument().toString().trim() === "" &&
                value.length > 0
            ) {
                trixElement.editor.loadHTML(value);
            }
        }
    }, [value]);

    return (
        <div className="trix-wrapper prose-sm max-w-none">
            <input
                id="trix-input"
                type="hidden"
                name="content"
                value={value || ""}
            />
            <trix-editor
                ref={trixRef}
                input="trix-input"
                placeholder={placeholder}
                class="trix-content border border-border bg-card text-foreground rounded-md min-h-[200px] p-3 focus:outline-none focus:ring-2 focus:ring-primary/20 max-h-[300px] overflow-y-auto"
            ></trix-editor>
        </div>
    );
};

export default function ChangeDialog({ open, onOpenChange, data }) {
    const isEdit = !!data;

    // MENGGUNAKAN useForm ASLI DARI INERTIA
    const {
        data: form,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        id: "",
        judul: "",
        status: "draft",
        isi: "",
        tanggal: new Date().toISOString().split("T")[0],
        author: "Admin",
        gambar: null,
        delete_gambar: false,
    });

    useEffect(() => {
        if (data) {
            setData({
                id: data.id_berita,
                judul: data.judul,
                status: data.status,
                isi: data.isi,
                tanggal: data.tanggal ? data.tanggal.split("T")[0] : "",
                author: data.author,
                gambar: null,
                delete_gambar: false,
            });
        } else {
            reset();
            setData("tanggal", new Date().toISOString().split("T")[0]);
        }
        clearErrors();
    }, [data, open]);

    const handleSubmit = (e) => {
        e.preventDefault();

        // MENGIRIM DATA KE SERVER (BUKAN MOCK)
        post(route("berita.store"), {
            onSuccess: () => {
                toast.success(
                    isEdit ? "Berita diperbarui!" : "Berita ditambahkan!"
                );
                onOpenChange(false);
                reset();
            },
            onError: () => toast.error("Gagal menyimpan, periksa inputan."),
        });
    };

    const headerColor = isEdit
        ? "bg-yellow-500 dark:bg-yellow-600 text-white"
        : "bg-primary text-primary-foreground";

    const headerTitle = isEdit ? "Edit Berita" : "Tambah Berita";

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                hideCloseButton
                className="sm:max-w-[700px] p-0 overflow-hidden gap-0 bg-background"
                aria-describedby={undefined}
            >
                <div
                    className={`${headerColor} px-6 py-4 flex justify-between items-center text-white`}
                >
                    <DialogTitle className="text-lg font-bold">
                        {headerTitle}
                    </DialogTitle>
                    <button
                        onClick={() => onOpenChange(false)}
                        className="text-white/80 hover:text-white"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="p-6 max-h-[85vh] overflow-y-auto">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Judul */}
                        <div className="space-y-1">
                            <Label
                                htmlFor="judul"
                                className="text-xs font-semibold text-foreground uppercase"
                            >
                                Judul <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="judul"
                                value={form.judul}
                                onChange={(e) =>
                                    setData("judul", e.target.value)
                                }
                                className="bg-card border-border"
                            />
                            {errors.judul && (
                                <p className="text-red-500 text-xs">
                                    {errors.judul}
                                </p>
                            )}
                        </div>

                        {/* Content Trix */}
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold text-muted-foreground uppercase">
                                Deskripsi
                            </Label>
                            <TrixEditor
                                value={form.isi}
                                onChange={(val) => setData("isi", val)}
                                placeholder="Tulis konten berita..."
                            />
                            {errors.isi && (
                                <p className="text-red-500 text-xs">
                                    {errors.isi}
                                </p>
                            )}
                        </div>

                        {/* Status */}
                        <div className="w-1/3">
                            <Label className="text-xs font-semibold text-foreground uppercase mb-1 block">
                                Status
                            </Label>
                            <Select
                                value={form.status}
                                onValueChange={(val) => setData("status", val)}
                            >
                                <SelectTrigger className="bg-card border-border">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">
                                        Published
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Gambar */}
                        {/* Gambar */}
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold text-muted-foreground uppercase">
                                Gambar Cover
                            </Label>

                            {/* Hanya tampilkan satu preview */}
                            {form.gambar ? (
                                <div className="relative w-full h-40 bg-card rounded-lg overflow-hidden mb-2 border border-border">
                                    <img
                                        src={URL.createObjectURL(form.gambar)}
                                        alt="Preview"
                                        className="w-full h-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setData("gambar", null)}
                                        className="absolute top-2 right-2 bg-destructive text-destructive-foreground p-1.5 rounded-full hover:bg-destructive/80"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            ) : data?.gambar && !form.delete_gambar ? (
                                <div className="relative w-full h-40 bg-card rounded-lg overflow-hidden mb-2 border border-border">
                                    <img
                                        src={`/storage/${data.gambar}`}
                                        alt="Preview"
                                        className="w-full h-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setData("delete_gambar", true)
                                        }
                                        className="absolute top-2 right-2 bg-destructive text-destructive-foreground p-1.5 rounded-full hover:bg-destructive/80"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            ) : null}

                            <label
                                htmlFor="file-upload"
                                className="flex flex-col items-center justify-center w-full h-24 border-2 border-border border-dashed rounded-lg cursor-pointer bg-card hover:bg-accent transition-colors"
                            >
                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                    <Upload className="w-6 h-6 text-muted-foreground mb-1" />
                                    <p className="text-xs text-muted-foreground">
                                        Klik untuk upload gambar
                                    </p>
                                </div>
                                <input
                                    id="file-upload"
                                    type="file"
                                    className="hidden"
                                    accept="image/*"
                                    onChange={(e) => {
                                        if (e.target.files[0])
                                            setData(
                                                "gambar",
                                                e.target.files[0]
                                            );
                                    }}
                                />
                            </label>
                            {errors.gambar && (
                                <p className="text-red-500 text-xs">
                                    {errors.gambar}
                                </p>
                            )}
                        </div>

                        <div className="flex justify-between pt-4 border-t mt-6">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-32"
                            >
                                {processing
                                    ? "Menyimpan..."
                                    : isEdit
                                    ? "Perbarui"
                                    : "Upload"}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                                className="w-32"
                            >
                                Batal
                            </Button>
                        </div>
                    </form>
                </div>
            </DialogContent>
        </Dialog>
    );
}
