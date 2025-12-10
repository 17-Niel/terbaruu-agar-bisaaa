import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
import { Checkbox } from "@/components/ui/checkbox";
import { useForm } from "@inertiajs/react";
import { Separator } from "@radix-ui/react-separator";
import { useEffect } from "react";
import { route } from "ziggy-js";

export function LowonganPekerjaanChangeDialog({
    dataEdit,
    dialogTitle,
    openDialog,
    setOpenDialog,
    // Menerima props dari parent (Page) agar lebih fleksibel
    perusahaanList,
    jenisLowonganList, 
}) {
    const pendidikanOptions = ["D3", "D4", "S1", "S2", "S3"];

    const { data, setData, post, processing, errors, reset } = useForm({
        id_lowongan: "",
        id_perusahaan: "",
        jenis_lowongan: "",   // UBAH: Dari id_jenis_lowongan menjadi jenis_lowongan
        nama_lowongan: "",
        departemen: "",
        deskripsi: "",
        kualifikasi: "",
        benefit: "",
        kualifikasi_pendidikan: [],
        link_pendaftaran: "",
        batas_akhir: "",
    });

    useEffect(() => {
        if (openDialog) {
            if (dataEdit && dataEdit.id_lowongan) {
                setData({
                    id_lowongan: dataEdit.id_lowongan || "",
                    id_perusahaan: dataEdit.id_perusahaan || "",
                    // UBAH: Mapping ke jenis_lowongan string
                    jenis_lowongan: dataEdit.jenis_lowongan || "", 
                    nama_lowongan: dataEdit.nama_lowongan || "",
                    departemen: dataEdit.departemen || "",
                    deskripsi: dataEdit.deskripsi || "",
                    kualifikasi: dataEdit.kualifikasi || "",
                    benefit: dataEdit.benefit || "",
                    kualifikasi_pendidikan: dataEdit.kualifikasi_pendidikan || [],
                    link_pendaftaran: dataEdit.link_pendaftaran || "",
                    batas_akhir: dataEdit.batas_akhir || "",
                });
            } else {
                reset();
            }
        }
    }, [dataEdit, openDialog]);

    const togglePendidikan = (value) => {
        const cur = data.kualifikasi_pendidikan || [];
        if (cur.includes(value)) {
            setData(
                "kualifikasi_pendidikan",
                cur.filter((v) => v !== value)
            );
        } else {
            setData("kualifikasi_pendidikan", [...cur, value]);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("lowongan-pekerjaan.change-post"), {
            onSuccess: () => {
                setOpenDialog(false);
                reset();
            },
        });
    };

    const safeList = (arr) => (arr?.data ? arr.data : arr || []);
    const today = new Date().toISOString().split("T")[0];

    return (
        <Sheet open={openDialog} onOpenChange={setOpenDialog}>
            <SheetContent className="w-[95vw] max-w-[900px] flex flex-col h-full">


                <SheetHeader className="pb-4">
                    <SheetTitle>{dialogTitle}</SheetTitle>
                    <SheetDescription>
                        Silahkan isi detail Lowongan Pekerjaan pada form di bawah ini.
                    </SheetDescription>
                </SheetHeader>

                <Separator className="border-b mb-4" />

                <form onSubmit={handleSubmit} className="flex flex-col flex-1 overflow-hidden">
                    <div className="grid flex-1 auto-rows-min gap-6 px-4 overflow-y-auto"> 
                        
                        {/* Nama Lowongan */}
                        <div className="grid gap-2">
                            <Label>Nama Posisi / Lowongan</Label>
                            <Input
                                value={data.nama_lowongan}
                                onChange={(e) =>
                                    setData("nama_lowongan", e.target.value)
                                }
                                placeholder="Contoh: Senior Backend Engineer"
                            />
                            {errors.nama_lowongan && (
                                <p className="text-sm text-red-600">
                                    {errors.nama_lowongan}
                                </p>
                            )}
                        </div>

                        {/* Perusahaan */}
                        <div className="grid gap-2">
                            <Label>Nama Perusahaan</Label>
                            <Select
                                value={data.id_perusahaan}
                                onValueChange={(v) =>
                                    setData("id_perusahaan", v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Perusahaan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        {safeList(perusahaanList).map((p) => (
                                            <SelectItem
                                                key={p.id_perusahaan}
                                                value={p.id_perusahaan}
                                            >
                                                {p.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            {errors.id_perusahaan && (
                                <p className="text-sm text-red-600">
                                    {errors.id_perusahaan}
                                </p>
                            )}
                        </div>

                        {/* Departemen */}
                        <div className="grid gap-2">
                            <Label>Departemen</Label>
                            <Input
                                value={data.departemen}
                                onChange={(e) =>
                                    setData("departemen", e.target.value)
                                }
                                placeholder="Contoh: IT Division"
                            />
                            {errors.departemen && (
                                <p className="text-sm text-red-600">
                                    {errors.departemen}
                                </p>
                            )}
                        </div>

                        {/* Jenis Lowongan (STATIS STRING) */}
                        <div className="grid gap-2">
                            <Label>Jenis Lowongan</Label>
                            <Select
                                // UBAH: Binding ke jenis_lowongan string
                                value={data.jenis_lowongan}
                                onValueChange={(v) =>
                                    setData("jenis_lowongan", v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Jenis Lowongan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        {/* UBAH: Mapping array string sederhana */}
                                        {safeList(jenisLowonganList).map((jenis) => (
                                            <SelectItem
                                                key={jenis}
                                                value={jenis}
                                            >
                                                {jenis}
                                            </SelectItem>
                                        ))}
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            {/* UBAH: Error key */}
                            {errors.jenis_lowongan && (
                                <p className="text-sm text-red-600">
                                    {errors.jenis_lowongan}
                                </p>
                            )}
                        </div>

                        {/* Deskripsi */}
                        <div className="grid gap-2">
                            <Label>Deskripsi Pekerjaan</Label>
                            <Textarea
                                rows={3}
                                value={data.deskripsi}
                                onChange={(e) =>
                                    setData("deskripsi", e.target.value)
                                }
                                placeholder="Jelaskan tanggung jawab pekerjaan..."
                            />
                            {errors.deskripsi && (
                                <p className="text-sm text-red-600">
                                    {errors.deskripsi}
                                </p>
                            )}
                        </div>

                        {/* Kualifikasi */}
                        <div className="grid gap-2">
                            <Label>Kualifikasi</Label>
                            <Textarea
                                rows={3}
                                value={data.kualifikasi}
                                onChange={(e) =>
                                    setData("kualifikasi", e.target.value)
                                }
                                placeholder="Skill, pengalaman, dll..."
                            />
                            {errors.kualifikasi && (
                                <p className="text-sm text-red-600">
                                    {errors.kualifikasi}
                                </p>
                            )}
                        </div>

                        {/* Benefit */}
                        <div className="grid gap-2">
                            <Label>Benefit</Label>
                            <Textarea
                                rows={2}
                                value={data.benefit}
                                onChange={(e) =>
                                    setData("benefit", e.target.value)
                                }
                                placeholder="Fasilitas, tunjangan, dll..."
                            />
                            {errors.benefit && (
                                <p className="text-sm text-red-600">
                                    {errors.benefit}
                                </p>
                            )}
                        </div>

                        {/* Kualifikasi Pendidikan */}
                        <div className="grid gap-2">
                            <Label>Kualifikasi Pendidikan</Label>
                            <div className="flex flex-wrap gap-3">
                                {pendidikanOptions.map((opt) => (
                                    <label
                                        key={opt}
                                        className="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            checked={data.kualifikasi_pendidikan.includes(
                                                opt
                                            )}
                                            onCheckedChange={() =>
                                                togglePendidikan(opt)
                                            }
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                            {errors.kualifikasi_pendidikan && (
                                <p className="text-sm text-red-600">
                                    {errors.kualifikasi_pendidikan}
                                </p>
                            )}
                        </div>

                        {/* Link Pendaftaran */}
                        <div className="grid gap-2">
                            <Label>Link Pendaftaran</Label>
                            <Input
                                value={data.link_pendaftaran}
                                onChange={(e) =>
                                    setData(
                                        "link_pendaftaran",
                                        e.target.value
                                    )
                                }
                                placeholder="https://..."
                            />
                            {errors.link_pendaftaran && (
                                <p className="text-sm text-red-600">
                                    {errors.link_pendaftaran}
                                </p>
                            )}
                        </div>

                        {/* Batas Akhir */}
                        <div className="grid gap-2">
                            <Label>Batas Akhir Pendaftaran</Label>
                            <Input
                                type="date"
                                min={today}
                                value={data.batas_akhir}
                                onChange={(e) => setData("batas_akhir", e.target.value)}
                            />
                            {errors.batas_akhir && (
                                <p className="text-sm text-red-600">
                                    {errors.batas_akhir}
                                </p>
                            )}
                        </div>
                    </div>

                    <SheetFooter>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Menyimpan..." : "Simpan"}
                        </Button>

                        <SheetClose asChild>
                            <Button variant="outline" disabled={processing}>
                                Batal
                            </Button>
                        </SheetClose>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>
    );
}