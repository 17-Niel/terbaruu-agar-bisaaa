import React from "react";
// IMPORT ASLI
import { useForm } from "@inertiajs/react";
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { route } from "ziggy-js"; // IMPORT ASLI

export default function DeleteDialog({ open, onOpenChange, data }) {
    // MENGGUNAKAN useForm ASLI
    const { post, processing } = useForm();

    const handleDelete = () => {
        // MENGIRIM REQUEST HAPUS KE SERVER
        post(route("berita.delete", { id: data?.id_berita }), {
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[400px] p-8 flex flex-col items-center justify-center text-center bg-card rounded-xl gap-6">
                <div className="w-20 h-20 rounded-full bg-destructive/10 border-2 border-destructive/20 flex items-center justify-center">
                    <span className="text-destructive text-5xl font-light">
                        !
                    </span>
                </div>

                <div className="space-y-2">
                    <DialogTitle className="text-xl font-bold text-foreground">
                        Hapus Berita?
                    </DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        Tindakan ini tidak dapat dibatalkan!
                    </DialogDescription>
                </div>

                <div className="flex w-full gap-3 justify-center mt-2">
                    <Button
                        type="button"
                        onClick={handleDelete}
                        disabled={processing}
                        className="w-32"
                    >
                        {processing ? "..." : "Ya, Hapus"}
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => onOpenChange(false)}
                        className="w-32"
                    >
                        Batal
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
