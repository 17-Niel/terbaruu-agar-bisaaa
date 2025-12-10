import React from "react";
import { router } from "@inertiajs/react";
import { X } from "lucide-react";
import * as Icon from "@tabler/icons-react";

export function ArtikelChangeDialog({
    dataEdit,
    openDialog,
    setOpenDialog,
    dialogTitle,
}) {
    const [form, setForm] = React.useState({
        artikelId: dataEdit?.artikelId || "",
        title: dataEdit?.title || "",
        content: dataEdit?.content || "",
        category: dataEdit?.category || "",
        isPublished: dataEdit?.isPublished || false,
        attachment: null,
    });

    React.useEffect(() => {
        setForm({
            artikelId: dataEdit?.artikelId || "",
            title: dataEdit?.title || "",
            content: dataEdit?.content || "",
            category: dataEdit?.category || "",
            isPublished: dataEdit?.isPublished || false,
            attachment: null,
        });
    }, [dataEdit]);

    if (!openDialog) return null;

    const handleFileChange = (e) => {
        const f = e.target.files?.[0];
        if (f) setForm({ ...form, attachment: f });
    };

    const handleSubmit = () => {
        if (!form.title || !form.content) {
            alert("Lengkapi field title & content");
            return;
        }

        const data = new FormData();
        data.append("id", form.artikelId);
        data.append("title", form.title);
        data.append("content", form.content);
        data.append("category", form.category);
        data.append("isPublished", form.isPublished ? "1" : "0");
        if (form.attachment) data.append("attachment", form.attachment);

        router.post("/artikel/change", data, {
            onSuccess: () => {
                setOpenDialog(false);
                router.visit("/artikel", {
                    preserveState: false,
                    only: ["artikelList"],
                });
            },
            onError: () => alert("Gagal menyimpan perubahan."),
        });
    };

    return (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-start pt-24 z-50">
            <div className="bg-background rounded-lg shadow-xl w-full max-w-2xl mx-4">
                <div className="bg-primary text-white px-6 py-3 rounded-t-lg flex items-center justify-between">
                    <h2 classNam e="text-lg font-semibold">
                        {dialogTitle}
                    </h2>
                    <button
                        onClick={() => setOpenDialog(false)}
                        className="p-1 rounded hover:bg-accent"
                        aria-label="Close modal"
                    >
                        <X size={18} />
                    </button>
                </div>

                <div className="p-6 space-y-4">
                    <div>
                        <label className="block mb-1 text-sm font-medium text-foreground">
                            Judul Artikel
                        </label>
                        <input
                            type="text"
                            value={form.title}
                            onChange={(e) =>
                                setForm({ ...form, title: e.target.value })
                            }
                            className="w-full border rounded-md px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label className="block mb-1 text-sm font-medium text-foreground">
                            Deskripsi Artikel
                        </label>
                        <textarea
                            value={form.content}
                            onChange={(e) =>
                                setForm({ ...form, content: e.target.value })
                            }
                            className="w-full border rounded-md px-3 py-2 text-sm h-36 resize-none"
                        />
                    </div>

                    <div>
                        <label className="block mb-1 text-sm font-medium text-foreground">
                            Sumber Artikel
                        </label>
                        <input
                            type="text"
                            value={form.category}
                            onChange={(e) =>
                                setForm({ ...form, category: e.target.value })
                            }
                            className="w-full border rounded-md px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label className="block mb-2 text-sm font-medium text-foreground">
                            Ganti File
                        </label>
                        <input
                            id="file-upload-edit"
                            type="file"
                            className="hidden"
                            accept="image/*,application/pdf"
                            onChange={handleFileChange}
                        />
                        <label
                            htmlFor="file-upload-edit"
                            className="inline-flex items-center gap-2 border border-input rounded-md px-3 py-2 cursor-pointer text-sm hover:"
                        >
                            <Icon.IconUpload size={16} />
                            <span>
                                {form.attachment
                                    ? form.attachment.name
                                    : "Pilih File Baru"}
                            </span>
                        </label>
                    </div>
                </div>

                <div className="p-6 flex justify-end">
                    <button
                        onClick={() => setOpenDialog(false)}
                        className="mr-3 px-4 py-2 rounded border border-input"
                    >
                        Batal
                    </button>
                    <button
                        onClick={handleSubmit}
                        className="bg-primary text-white px-4 py-2 rounded hover:brightness-110"
                    >
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    );
}
