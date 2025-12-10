import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { useForm } from "@inertiajs/react";
import { Separator } from "@radix-ui/react-separator";
import { useEffect, useState } from "react";
import { route } from "ziggy-js";

/**
 * Komponen Dialog untuk menambah atau mengubah data Banner
 *
 * @param {Object} props - Props komponen
 * @param {Object} props.dataEdit - Data banner yang akan diedit (null untuk tambah baru)
 * @param {string} props.dialogTitle - Judul dialog
 * @param {boolean} props.openDialog - Status buka/tutup dialog
 * @param {Function} props.setOpenDialog - Fungsi untuk mengontrol buka/tutup dialog
 */
export function BannerChangeDialog({
    dataEdit,
    dialogTitle,
    openDialog,
    setOpenDialog,
}) {
    // ============================ STATE ============================
    // State untuk menampung preview gambar (URL string)
    const [imagePreview, setImagePreview] = useState(null);

    // ============================ FORM MANAGEMENT ============================

    // Inisialisasi form menggunakan useForm dari Inertia
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        bannerId: "",
        nama_banner: "", // State untuk nama banner
        gambar: null,    // File object, biarkan null
        shown: true,     // Default true
    });

    // ============================ EFFECTS ============================

    /**
     * Effect untuk mengisi form ketika dataEdit berubah atau dialog dibuka
     */
    useEffect(() => {
        if (openDialog) {
            clearErrors(); // Bersihkan error sebelumnya saat dialog dibuka
            
            if (dataEdit && dataEdit.bannerId) {
                // === Mode EDIT ===
                setData({
                    bannerId: dataEdit.bannerId || "",
                    nama_banner: dataEdit.nama_banner || "", // Isi nama dari dataEdit
                    gambar: null, // Reset input file saat edit dibuka
                    shown: dataEdit.shown === undefined ? true : !!dataEdit.shown,
                });
                
                // Set preview dari URL yang ada di database
                setImagePreview(dataEdit.url_gambar || null);
            } else {
                // === Mode TAMBAH (Create) ===
                setData({
                    bannerId: "",
                    nama_banner: "",
                    gambar: null,
                    shown: true,
                });
                setImagePreview(null);
            }
        }
    }, [dataEdit, openDialog]);

    /**
     * Effect untuk handle preview saat user memilih file baru
     */
    useEffect(() => {
        if (data.gambar instanceof File) {
            // Buat URL object sementara untuk preview file yang baru dipilih
            const objectUrl = URL.createObjectURL(data.gambar);
            setImagePreview(objectUrl);

            // Cleanup memory ketika component unmount atau gambar berubah
            return () => URL.revokeObjectURL(objectUrl);
        }
    }, [data.gambar]);

    // ============================ FUNCTIONS ============================

    /**
     * Handle submit form
     * Mengirim data form ke endpoint banner.change-post
     */
    const handleSubmit = () => {
        post(route("banner.change-post"), {
            forceFormData: true, // Memaksa pengiriman sebagai FormData untuk upload file
            onSuccess: () => {
                reset();
                setImagePreview(null);
                setOpenDialog(false); // Tutup dialog jika sukses
            },
            onError: (err) => {
                console.error("Error submitting form:", err);
            }
        });
    };

    // ============================ RENDER ============================

    return (
        <Sheet open={openDialog} onOpenChange={setOpenDialog}>
            <SheetContent aria-describedby="form-dialog" className="w-[400px] sm:w-[540px]">
                {/* Header Dialog */}
                <SheetHeader className="pb-4">
                    <SheetTitle>{dialogTitle}</SheetTitle>
                    <SheetDescription>
                        Silahkan isi detail banner dan upload gambar pada form di bawah ini. Urutan akan diatur otomatis oleh sistem.
                    </SheetDescription>
                </SheetHeader>

                <Separator className="border-b mb-4" />

                {/* Form Input */}
                <div className="grid flex-1 auto-rows-min gap-6 px-4 overflow-y-auto">
                    
                    {/* Input Nama Banner (Wajib Diisi) */}
                    <div className="grid gap-3">
                        <Label htmlFor="inputNama">Nama Banner</Label>
                        <Input
                            id="inputNama"
                            value={data.nama_banner}
                            onChange={(e) => setData("nama_banner", e.target.value)}
                            placeholder="Contoh: Banner Desember 2019"
                        />
                        {errors.nama_banner && (
                            <p className="text-sm text-red-600">
                                {errors.nama_banner}
                            </p>
                        )}
                    </div>

                    {/* Input Upload Gambar */}
                    <div className="grid gap-3">
                        <Label htmlFor="inputGambar">Upload Gambar</Label>
                        <Input
                            id="inputGambar"
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) {
                                    setData("gambar", file);
                                }
                            }}
                            className="cursor-pointer"
                        />
                        
                        {/* Area Preview */}
                        <div className="mt-2 grid gap-2">
                            <Label className="text-xs text-muted-foreground">Preview:</Label>
                            {imagePreview ? (
                                <div className="relative h-40 w-full overflow-hidden rounded-md border bg-gray-50 flex items-center justify-center">
                                    <img 
                                        src={imagePreview} 
                                        alt="Preview Banner" 
                                        className="h-full w-full object-contain"
                                        onError={(e) => {
                                            // Sembunyikan gambar jika error (misal URL rusak/404)
                                            e.target.style.display = 'none'; 
                                            // Opsional: Tampilkan fallback text
                                            e.target.parentNode.innerText = "Gagal memuat preview gambar";
                                            e.target.parentNode.className += " text-xs text-muted-foreground p-2 text-center";
                                        }}
                                    />
                                </div>
                            ) : (
                                <div className="h-20 w-full rounded-md border border-dashed bg-gray-50 flex items-center justify-center text-xs text-muted-foreground">
                                    Belum ada gambar
                                </div>
                            )}
                        </div>

                        {/* Error Message */}
                        {errors.gambar && (
                            <p className="text-sm text-red-600">
                                {errors.gambar}
                            </p>
                        )}
                    </div>

                    {/* Checkbox Status Shown */}
                    <div className="flex items-start space-x-3 space-y-0 rounded-md border p-4 shadow-sm">
                        <Checkbox
                            id="checkShown"
                            checked={!!data.shown}
                            onCheckedChange={(checked) =>
                                setData("shown", !!checked)
                            }
                        />
                        <div className="grid gap-1.5 leading-none">
                            <Label
                                htmlFor="checkShown"
                                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                            >
                                Tampilkan Banner
                            </Label>
                            <p className="text-sm text-muted-foreground">
                                Jika dicentang, banner akan muncul di halaman depan website.
                            </p>
                        </div>
                    </div>
                    {errors.shown && (
                        <p className="text-sm text-red-600">
                            {errors.shown}
                        </p>
                    )}

                </div>

                {/* Footer Dialog - Tombol Aksi */}
                <SheetFooter className="mt-8">
                    {/* Tombol Simpan */}
                    <Button
                        onClick={handleSubmit}
                        type="button"
                        disabled={processing}
                        className="min-w-24"
                    >
                        {processing ? "Menyimpan..." : "Simpan"}
                    </Button>

                    {/* Tombol Batal */}
                    <SheetClose asChild>
                        <Button
                            variant="outline"
                            type="button"
                            disabled={processing}
                        >
                            Batal
                        </Button>
                    </SheetClose>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}