import { Button } from "@/components/ui/button";
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
import { Textarea } from "@/components/ui/textarea";
import { useForm } from "@inertiajs/react";
import { Separator } from "@radix-ui/react-separator";
import { useEffect } from "react";
import { route } from "ziggy-js";

/**
 * Komponen Dialog untuk menambah atau mengubah data Perusahaan
 *
 * @param {Object} props
 */
export function PerusahaanChangeDialog({
    dataEdit,
    dialogTitle,
    openDialog,
    setOpenDialog,
}) {
    // ============================ FORM MANAGEMENT ============================

    const { data, setData, post, processing, errors, reset } = useForm({
        id_perusahaan: "",
        nama: "",
        industri: "",
        lokasi: "",
        website: "",
        deskripsi: "",
        url_logo: null, // [UBAH] Inisialisasi dengan null, bukan string kosong
    });

    // ============================ EFFECTS ============================

    useEffect(() => {
        if (dataEdit && dataEdit.id_perusahaan) {
            // Mode edit: isi form dengan data yang ada
            setData({
                id_perusahaan: dataEdit.id_perusahaan || "",
                nama: dataEdit.nama || "",
                industri: dataEdit.industri || "",
                lokasi: dataEdit.lokasi || "",
                website: dataEdit.website || "",
                deskripsi: dataEdit.deskripsi || "",
                // [PERBAIKAN PENTING]: Jangan isi url_logo dengan string URL lama.
                // Biarkan null agar backend tahu kita tidak mengupload file baru.
                url_logo: null, 
            });
        } else {
            // Mode tambah: reset form
            reset();
        }
    }, [dataEdit]);

    // ============================ FUNCTIONS ============================

    // [PERBAIKAN] Fungsi ini ditambahkan agar tidak error saat upload file
    const handleFileChange = (e) => {
        if (e.target.files && e.target.files[0]) {
            setData("url_logo", e.target.files[0]);
        }
    };

    const handleSubmit = () => {
        post(route("perusahaan.change-post"), {
            onSuccess: () => {
                setOpenDialog(false);
                reset();
            },
        });
    };

    // ============================ RENDER ============================

    return (
        <>
            <Sheet open={openDialog} onOpenChange={setOpenDialog}>
                <SheetContent aria-describedby="form-dialog" >
                    <SheetHeader className="pb-0">
                        <SheetTitle>{dialogTitle}</SheetTitle>
                        <SheetDescription>
                            Silahkan isi data perusahaan pada form di bawah ini.
                        </SheetDescription>
                    </SheetHeader>

                    <Separator className="border-b my-4" />

                    
                    <div className="grid flex-1 auto-rows-min gap-6 px-4 overflow-y-auto">
                        <div className="grid flex-1 auto-rows-min gap-6 px-1">
                            
                            {/* Input Nama Perusahaan */}
                            <div className="grid gap-2">
                                <Label htmlFor="inputNama">Nama Perusahaan <span className="text-red-500">*</span></Label>
                                <Input
                                    id="inputNama"
                                    value={data.nama}
                                    onChange={(e) => setData("nama", e.target.value)}
                                    placeholder="PT Contoh Sejahtera..."
                                />
                                {errors.nama && (
                                    <p className="text-sm text-red-600">{errors.nama}</p>
                                )}
                            </div>

                            {/* Input Industri */}
                            <div className="grid gap-2">
                                <Label htmlFor="inputIndustri">Industri</Label>
                                <Input
                                    id="inputIndustri"
                                    value={data.industri}
                                    onChange={(e) => setData("industri", e.target.value)}
                                    placeholder="Contoh: Teknologi, Pertambangan..."
                                />
                                {errors.industri && (
                                    <p className="text-sm text-red-600">{errors.industri}</p>
                                )}
                            </div>

                            {/* Input Website */}
                            <div className="grid gap-2">
                                <Label htmlFor="inputWebsite">Website</Label>
                                <Input
                                    id="inputWebsite"
                                    value={data.website}
                                    onChange={(e) => setData("website", e.target.value)}
                                    placeholder="https://example.com"
                                />
                                {errors.website && (
                                    <p className="text-sm text-red-600">{errors.website}</p>
                                )}
                            </div>

                            {/* Input URL Logo */}
                            <div className="grid gap-2">
                                <Label htmlFor="inputUrlLogo">Logo</Label>
                                <Input
                                    id="inputUrlLogo"
                                    type="file"      
                                    accept="image/*" 
                                    onChange={handleFileChange}
                                />
                                {/* [PERBAIKAN LOGIKA TAMPILAN]: 
                                    Cek dataEdit.url_logo untuk menampilkan status gambar lama.
                                    Cek data.url_logo (form) untuk melihat apakah user sedang memilih file baru.
                                */}
                                
                                {/* Skenario 1: Ada data lama, dan user BELUM memilih file baru */}
                                {dataEdit?.url_logo && !data.url_logo && (
                                    <div className="flex items-center gap-2 mt-1">
                                        <p className="text-xs text-green-600 font-medium">
                                            ✓ Gambar logo lama tersimpan.
                                        </p>
                                        {/* Opsional: Tampilkan preview kecil */}
                                        <a href={dataEdit.url_logo} target="_blank" className="text-xs text-blue-500 underline" rel="noreferrer">Lihat</a>
                                    </div>
                                )}

                                {/* Skenario 2: User memilih file baru */}
                                {data.url_logo && (
                                    <p className="text-xs text-blue-600 mt-1">
                                        File baru dipilih: {data.url_logo.name}
                                    </p>
                                )}

                                {errors.url_logo && (
                                    <p className="text-sm text-red-600">{errors.url_logo}</p>
                                )}
                            </div>

                            {/* Input Lokasi (Textarea) */}
                            <div className="grid gap-2">
                                <Label htmlFor="inputLokasi">Lokasi / Alamat</Label>
                                <Textarea
                                    id="inputLokasi"
                                    value={data.lokasi}
                                    onChange={(e) => setData("lokasi", e.target.value)}
                                    placeholder="Alamat lengkap perusahaan..."
                                    rows={2}
                                />
                                {errors.lokasi && (
                                    <p className="text-sm text-red-600">{errors.lokasi}</p>
                                )}
                            </div>

                            {/* Input Deskripsi */}
                            <div className="grid gap-2">
                                <Label htmlFor="inputDescription">Deskripsi</Label>
                                <Textarea
                                    id="inputDescription"
                                    value={data.deskripsi}
                                    onChange={(e) => setData("deskripsi", e.target.value)}
                                    placeholder="Deskripsi singkat perusahaan..."
                                    rows={4}
                                />
                                {errors.deskripsi && (
                                    <p className="text-sm text-red-600">{errors.deskripsi}</p>
                                )}
                            </div>

                        </div>
                    </div>

                    <SheetFooter className="mt-6">
                        <Button
                            onClick={handleSubmit}
                            type="button"
                            disabled={processing}
                            className="min-w-20"
                        >
                            {processing ? "Menyimpan..." : "Simpan"}
                        </Button>

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
        </>
    );
}