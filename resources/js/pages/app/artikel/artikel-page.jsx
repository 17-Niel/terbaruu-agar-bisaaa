// --- FULL FILE START ---
import { X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import AppLayout from "@/layouts/app-layout";
import { router, usePage } from "@inertiajs/react";
import * as Icon from "@tabler/icons-react";
import dayjs from "dayjs";
import * as React from "react";
import { toast } from "sonner";
import { ArtikelChangeDialog } from "./dialogs/change-dialog";
import { ArtikelDeleteDialog } from "./dialogs/delete-dialog";

export default function ArtikelPage() {
    const {
        artikelList = { data: [], current_page: 1, per_page: 10, last_page: 1 },
        flash = {},
        isEditor = false,
        perPage = 10,
        search: initialSearch,
        page: initialPage,
    } = usePage().props || {};

    const [search, setSearch] = React.useState(initialSearch || "");
    const [debouncedSearch, setDebouncedSearch] = React.useState("");
    const [isModalOpen, setIsModalOpen] = React.useState(false);

    const [form, setForm] = React.useState({
        judul: "",
        deskripsi: "",
        sumberArtikel: "",
        gambar: null,
    });

    const [artikelData, setArtikelData] = React.useState(
        artikelList.data || []
    );
    const [isChangeDialogOpen, setIsChangeDialogOpen] = React.useState(false);
    const [dataEdit, setDataEdit] = React.useState(null);

    const [dataDelete, setDataDelete] = React.useState(null);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = React.useState(false);

    const isFirst = React.useRef(true);

    React.useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search), 500);
        return () => clearTimeout(timer);
    }, [search]);

    React.useEffect(() => {
        setArtikelData(Array.isArray(artikelList.data) ? artikelList.data : []);
    }, [artikelList.data]);

    React.useEffect(() => {
        const targetPage = isFirst.current ? initialPage : 1;
        isFirst.current = false;

        if (debouncedSearch !== undefined) {
            handlePagination(
                `/artikel?page=${targetPage}&search=${encodeURIComponent(
                    debouncedSearch
                )}`,
                debouncedSearch
            );
        }
    }, [debouncedSearch]);

    React.useEffect(() => {
        if (flash && flash.success) {
            toast.success(flash.success);

            setIsChangeDialogOpen(false);
            setIsDeleteDialogOpen(false);
        }
        if (flash && flash.error) toast.error(flash.error);
    }, [flash]);

    const handlePagination = (pageUrl, searchVal) => {
        setSearch(searchVal);
        router.visit(pageUrl, {
            preserveState: true,
            replace: true,
            only: ["artikelList"],
        });
    };

    const handleFileChange = (e) => {
        const file = e.target.files && e.target.files[0];
        if (file) setForm({ ...form, gambar: file });
    };

    const handleSimpan = () => {
        if (!form.judul || !form.deskripsi || !form.sumberArtikel) {
            toast.error("Mohon lengkapi semua field!");
            return;
        }

        const data = new FormData();
        data.append("title", form.judul);
        data.append("content", form.deskripsi);
        data.append("category", form.sumberArtikel);
        if (form.gambar) data.append("attachment", form.gambar);

        router.post("/artikel/change", data, {
            onSuccess: () => {
                setIsModalOpen(false);
                setForm({
                    judul: "",
                    deskripsi: "",
                    sumberArtikel: "",
                    gambar: null,
                });

                router.visit("/artikel", {
                    preserveState: false,
                    only: ["artikelList"],
                });
            },
            onError: () => {
                toast.error("Gagal menambahkan artikel!");
            },
        });
    };

    return (
        <AppLayout>
            <div className="py-8">
                <div className="max-w-6xl mx-auto">
                    <h1 className="text-3xl font-bold text-center mb-6">
                        Artikel
                    </h1>

                    <div className="flex items-center justify-center gap-6 mb-8">
                        <div className="w-1/2">
                            <input
                                type="text"
                                placeholder="Cari..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="
    w-full border rounded-md px-4 py-2
    bg-background text-foreground
    placeholder-muted-foreground
    border-input shadow-sm
    focus:ring-2 focus:ring-primary/40
    transition
"
                            />
                        </div>

                        <div className="ml-4 mt-20">
                            <button
                                onClick={() => setIsModalOpen(true)}
                                className="inline-flex items-center
            bg-primary            /* ← otomatis ikut theme */
            text-primary-foreground
            px-5 py-1.5
            rounded-[15px]
            text-xl font-semibold
            gap-2
            shadow-[0_4px_12px_rgba(93,135,255,0.32)]
            hover:brightness-105
            active:scale-[0.98]
            transition-all"
                            >
                                <span className="text-lg font-light flex items-center">
                                    + Tambah
                                </span>
                            </button>
                        </div>
                    </div>

                    <div className="bg-card text-card-foreground rounded-lg shadow overflow-hidden transition-colors">
                        <table className="w-full text-left ">
                            <thead>
                                <tr>
                                    <th className="px-6 py-3 font-semibold bg-gray-100 text-gray-700">
                                        No
                                    </th>
                                    <th className="px-6 py-3 font-semibold bg-gray-100 text-gray-700">
                                        Judul
                                    </th>
                                    <th className="px-6 py-3 font-semibold bg-gray-100 text-gray-700">
                                        Deskripsi
                                    </th>
                                    <th className="px-6 py-3 font-semibold bg-gray-100 text-gray-700">
                                        Sumber
                                    </th>
                                    <th className="px-6 py-3 font-semibold bg-gray-100 text-gray-700">
                                        Lampira
                                    </th>
                                    <th className="px-6 py-3 font-semibold bg-gray-100 text-center text-gray-700">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                {Array.isArray(artikelData) &&
                                artikelData.length > 0 ? (
                                    artikelData.map((it, idx) => (
                                        <tr
                                            key={it.id || idx}
                                            className="border-b border-border hover:bg-muted/30 transition-colors"
                                        >
                                            <td className="px-6 py-3 text-foreground">
                                                {(artikelList.current_page -
                                                    1) *
                                                    artikelList.per_page +
                                                    idx +
                                                    1}
                                            </td>

                                            <td className="px-6 py-3 text-foreground">
                                                {it.title}
                                            </td>

                                            <td className="px-6 py-3 text-foreground">
                                                {it.content?.substring(0, 50)}
                                                ...
                                            </td>

                                            <td className="px-6 py-3 text-foreground">
                                                {it.category}
                                            </td>

                                            <td className="px-6 py-3 text-foreground">
                                                {it.attachment ? (
                                                    <button
                                                        onClick={() =>
                                                            window.open(
                                                                it.attachment_url,
                                                                "_blank",
                                                                "noopener,noreferrer"
                                                            )
                                                        }
                                                        className="
                px-3 py-1 text-xs rounded
                bg-secondary 
                text-secondary-foreground
                border border-input
                hover:bg-accent 
                transition
            "
                                                    >
                                                        Lihat Lampiran
                                                    </button>
                                                ) : (
                                                    "-"
                                                )}
                                            </td>

                                            <td className="px-6 py-3 text-center">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <button
                                                            className="p-2 rounded hover:bg-muted
 transition"
                                                        >
                                                            <Icon.IconDotsVertical
                                                                size={20}
                                                            />
                                                        </button>
                                                    </DropdownMenuTrigger>

                                                    <DropdownMenuContent className="w-36 ">
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                router.visit(
                                                                    `/artikel/detail/${it.id}`
                                                                )
                                                            }
                                                            className="cursor-pointer flex items-center gap-2"
                                                        >
                                                            <Icon.IconEye
                                                                size={16}
                                                            />
                                                            Detail
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem
                                                            onClick={() => {
                                                                setDataEdit({
                                                                    artikelId:
                                                                        it.id,
                                                                    title: it.title,
                                                                    content:
                                                                        it.content,
                                                                    category:
                                                                        it.category,
                                                                    isPublished:
                                                                        it.is_published,
                                                                });
                                                                setIsChangeDialogOpen(
                                                                    true
                                                                );
                                                            }}
                                                            className="cursor-pointer flex items-center gap-2"
                                                        >
                                                            <Icon.IconEdit
                                                                size={16}
                                                            />
                                                            Edit
                                                        </DropdownMenuItem>

                                                        <DropdownMenuSeparator />

                                                        <DropdownMenuItem
                                                            onClick={() => {
                                                                setDataDelete({
                                                                    artikelIds:
                                                                        [it.id],
                                                                    dataList: [
                                                                        {
                                                                            id: it.id,
                                                                            title: it.title,
                                                                        },
                                                                    ],
                                                                });
                                                                setIsDeleteDialogOpen(
                                                                    true
                                                                );
                                                            }}
                                                            className="text-red-500 cursor-pointer flex items-center gap-2"
                                                        >
                                                            <Icon.IconTrash
                                                                size={16}
                                                            />
                                                            Hapus
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td
                                            colSpan="6"
                                            className="px-6 py-12 text-center text-gray-500"
                                        >
                                            Belum ada data tersedia.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {isModalOpen && (
                <div className="fixed inset-0 bg-black/40 flex justify-center items-start pt-24 z-50">
                    <div className="bg-card text-card-foreground rounded-lg shadow-xl w-full max-w-xl mx-4 transition-colors">
                        <div className="bg-primary text-primary-foreground px-6 py-3 rounded-t-lg flex items-center justify-between">
                            <h2 className="text-lg font-semibold">
                                Tambah Artikel
                            </h2>
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="p-1 rounded hover:bg-primary/80 transition"
                                aria-label="Close modal"
                            >
                                <X size={18} />
                            </button>
                        </div>

                        <div className="p-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block mb-1 font-medium text-foreground">
                                        Judul Artikel
                                    </label>
                                    <input
                                        type="text"
                                        value={form.judul}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                judul: e.target.value,
                                            })
                                        }
                                        className="w-full border border-input rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    />
                                </div>

                                <div>
                                    <label className="block mb-1 font-medium text-foreground">
                                        Deskripsi Artikel
                                    </label>
                                    <textarea
                                        value={form.deskripsi}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                deskripsi: e.target.value,
                                            })
                                        }
                                        className="w-full border border-input rounded-md px-3 py-2 h-36 resize-none focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                    />
                                </div>

                                <div>
                                    <label className="block mb-1 font-medium text-foreground">
                                        Sumber Artikel
                                    </label>
                                    <input
                                        type="text"
                                        value={form.sumberArtikel}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                sumberArtikel: e.target.value,
                                            })
                                        }
                                        className="w-full border border-input rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                    />
                                </div>

                                <div>
                                    <label className="block mb-2 font-medium text-foreground">
                                        Tambahkan File
                                    </label>

                                    <input
                                        id="file-upload"
                                        type="file"
                                        className="hidden"
                                        accept="image/*,application/pdf"
                                        onChange={handleFileChange}
                                    />

                                    <label
                                        htmlFor="file-upload"
                                        className="inline-flex items-center gap-2 border border-input rounded-md px-3 py-2 cursor-pointer hover:bg-gray-50"
                                    >
                                        <Icon.IconUpload size={16} />
                                        <span>
                                            {form.gambar
                                                ? form.gambar.name
                                                : "Choose a File"}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div className="p-6 flex justify-end">
                            <button
                                onClick={handleSimpan}
                                className="
    bg-background border border-primary/40 
    px-4 py-2 rounded 
    text-primary 
    hover:bg-primary hover:text-primary-foreground
    transition
"
                            >
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {isChangeDialogOpen && dataEdit && (
                <ArtikelChangeDialog
                    dataEdit={dataEdit}
                    openDialog={isChangeDialogOpen}
                    setOpenDialog={setIsChangeDialogOpen}
                    dialogTitle="Ubah Artikel"
                />
            )}

            {isDeleteDialogOpen && dataDelete && (
                <ArtikelDeleteDialog
                    open={isDeleteDialogOpen}
                    onOpenChange={setIsDeleteDialogOpen}
                    data={dataDelete}
                />
            )}
        </AppLayout>
    );
}
// --- FULL FILE END ---
