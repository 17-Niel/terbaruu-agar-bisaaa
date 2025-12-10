import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
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
import { useForm } from "@inertiajs/react";
import { Separator } from "@radix-ui/react-separator";
import { AlertCircleIcon } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { route } from "ziggy-js";

/**
 * Dialog untuk menghapus data Lowongan Pekerjaan
 *
 * @param {Object} props
 * @param {Object} props.dataDelete - Data yang akan dihapus { ids_lowongan: [], dataList: [] }
 * @param {boolean} props.openDialog
 * @param {Function} props.setOpenDialog
 */
export function LowonganPekerjaanDeleteDialog({
    dataDelete,
    openDialog,
    setOpenDialog,
}) {
    // ============================ FORM MANAGEMENT ============================

    const { data, setData, post, processing } = useForm({
        // PERBAIKAN: Ubah 'lowonganIds' menjadi 'ids_lowongan' agar sesuai dengan Controller
        ids_lowongan: [],
        confirmation: "",
    });

    // Kunci konfirmasi yang harus diketik ulang
    const [keyConfirmation, setKeyConfirmation] = useState("");

    // ============================ EFFECTS ============================

    /**
     * Generate key konfirmasi ketika dataDelete berubah
     */
    useEffect(() => {
        // PERBAIKAN: Cek 'ids_lowongan', bukan 'lowonganIds'
        if (dataDelete && dataDelete.ids_lowongan) {
            const randomKey = Math.random()
                .toString(36)
                .substring(2, 8)
                .toUpperCase();
            setKeyConfirmation(randomKey);
        } else {
            setKeyConfirmation("");
        }
    }, [dataDelete]);

    /**
     * Set daftar ID lowongan yang akan dihapus ke dalam form
     */
    useEffect(() => {
        // PERBAIKAN: Ambil dari 'ids_lowongan' dan set ke state form 'ids_lowongan'
        if (dataDelete && dataDelete.ids_lowongan) {
            setData("ids_lowongan", dataDelete.ids_lowongan || []);
        }
        // Jangan setOpenDialog(false) di sini agar animasi penutup berjalan mulus dari parent
    }, [dataDelete]);

    // ============================ FUNCTIONS ============================

    /**
     * Handle submit penghapusan
     */
    const handleSubmit = () => {
        if (data.confirmation !== keyConfirmation) {
            toast.error("Konfirmasi kunci tidak sesuai.");
            return;
        }

        post(route("lowongan-pekerjaan.delete-post"), {
            onSuccess: () => {
                setOpenDialog(false);
                // Reset form confirmation manual agar bersih saat dibuka kembali
                setData("confirmation", ""); 
            },
            onError: (errors) => {
                toast.error("Gagal menghapus data. Silakan coba lagi.");
                console.error("Error deleting:", errors);
            }
        });
    };

    // ============================ RENDER ============================

    return (
        <>
            <Sheet open={openDialog} onOpenChange={setOpenDialog}>
                <SheetContent aria-describedby="form-dialog">
                    {/* Header */}
                    <SheetHeader className="pb-4">
                        <SheetTitle>Hapus Lowongan Pekerjaan</SheetTitle>
                        <SheetDescription>
                            Tindakan ini akan menghapus lowongan pekerjaan yang dipilih dari sistem.
                        </SheetDescription>
                    </SheetHeader>

                    <Separator className="border-b mb-6" />

                    {/* Content */}
                    <div className="grid flex-1 auto-rows-min gap-6 px-1">

                        {/* Alert peringatan */}
                        <div className="grid gap-3">
                            <Alert variant="destructive">
                                <AlertCircleIcon className="h-4 w-4" />
                                <AlertTitle>Peringatan Penghapusan</AlertTitle>
                                <AlertDescription>
                                    <p className="mb-2">
                                        Anda akan menghapus lowongan pekerjaan berikut ini.
                                        Tindakan ini bersifat permanen dan tidak dapat dipulihkan.
                                    </p>

                                    <ul className="list-inside list-disc text-sm space-y-1 max-h-40 overflow-y-auto">
                                        {dataDelete?.dataList
                                            ? dataDelete.dataList.map((lw) => (
                                                  <li
                                                      key={`delete-${lw.id}`}
                                                      className="text-destructive-foreground font-medium"
                                                  >
                                                      {lw.title}
                                                  </li>
                                              ))
                                            : null}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        </div>

                        {/* Kunci konfirmasi */}
                        <div className="grid gap-3">
                            <Label htmlFor="keyConfirmation">
                                Kunci untuk menghapus
                            </Label>
                            <Input
                                id="keyConfirmation"
                                value={keyConfirmation}
                                readOnly
                                disabled
                                className="font-mono text-center text-lg tracking-widest bg-muted"
                            />
                        </div>

                        {/* Input konfirmasi */}
                        <div className="grid gap-3">
                            <Label htmlFor="userConfirmation">
                                Tulis ulang kunci di atas
                            </Label>
                            <Input
                                id="userConfirmation"
                                value={data.confirmation}
                                onChange={(e) =>
                                    setData("confirmation", e.target.value.toUpperCase())
                                }
                                placeholder="Ketik kunci di sini..."
                                className="font-mono text-center uppercase"
                                autoComplete="off"
                            />
                        </div>
                    </div>

                    {/* Footer */}
                    <SheetFooter className="flex gap-2 mt-8">
                        <Button
                            onClick={handleSubmit}
                            type="button"
                            className="bg-red-600 hover:bg-red-700 min-w-24"
                            disabled={processing || !data.confirmation || data.confirmation !== keyConfirmation}
                        >
                            {processing ? "Menghapus..." : "Hapus"}
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