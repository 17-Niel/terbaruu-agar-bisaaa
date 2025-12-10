import React, { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { Head, router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
} from "@/components/ui/pagination";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Plus, Pencil, Trash2, Eye, Star } from "lucide-react";
import ChangeDialog from "./dialogs/change-dialog";
import DeleteDialog from "./dialogs/delete-dialog";
import { route } from "ziggy-js";

export default function BeritaPage({ berita, stats, filters }) {
    // Pastikan stats memiliki nilai default jika undefined
    const safeStats = stats || { total: 0, published: 0, draft: 0, views: 0 };

    const [searchTerm, setSearchTerm] = useState(filters.search || "");
    const [statusFilter, setStatusFilter] = useState(filters.status || "all");
    const [openChange, setOpenChange] = useState(false);
    const [openDelete, setOpenDelete] = useState(false);
    const [selectedData, setSelectedData] = useState(null);

    const handleSearch = (term) => {
        setSearchTerm(term);
        // PERBAIKAN: Gunakan 'berita.index' bukan 'app.berita.index'
        router.get(
            route("berita.index"),
            { search: term, status: statusFilter },
            { preserveState: true, replace: true }
        );
    };

    const handleFilterStatus = (val) => {
        setStatusFilter(val);
        // PERBAIKAN: Gunakan 'berita.index' bukan 'app.berita.index'
        router.get(
            route("berita.index"),
            { search: searchTerm, status: val },
            { preserveState: true, replace: true }
        );
    };

    const openAdd = () => {
        setSelectedData(null);
        setOpenChange(true);
    };

    const openEdit = (item) => {
        setSelectedData(item);
        setOpenChange(true);
    };

    const openDel = (item) => {
        setSelectedData(item);
        setOpenDelete(true);
    };

    const getStatusBadge = (status) => {
        if (status === "published") {
            return (
                <div className="px-3 py-1 rounded-md bg-primary/10 text-primary text-xs font-semibold border border-primary/20">
                    Published
                </div>
            );
        }
        return (
            <div className="px-3 py-1 rounded-md bg-muted text-muted-foreground text-xs font-semibold border border-border">
                Draft
            </div>
        );
    };

    return (
        <AppLayout>
            <Head title="Manajemen Berita" />
           <div className="p-6 space-y-6">
                {/* --- HEADER SECTION --- */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900">
                            Berita
                        </h2>
                        <p className="text-sm text-gray-500 mt-1">
                            Kelolah berita dan informasi terbaru.
                        </p>
                    </div>
                    <div>
                        <Button
                            onClick={openAdd}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 shadow-md"
                        >
                            Tambah Berita
                        </Button>
                    </div>
                </div>

                {/* Dashboard Cards Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="bg-card p-4 rounded-xl shadow-sm border border-border flex flex-col items-center justify-center">
                        <span className="text-muted-foreground font-medium">
                            Total Berita
                        </span>
                        <span className="text-2xl font-bold text-foreground">
                            {safeStats.total}
                        </span>
                    </div>

                    <div className="bg-card p-4 rounded-xl shadow-sm border border-border flex flex-col items-center justify-center">
                        <span className="text-muted-foreground font-medium">
                            Published
                        </span>
                        <span className="text-2xl font-bold text-foreground">
                            {safeStats.published}
                        </span>
                    </div>

                    <div className="bg-card p-4 rounded-xl shadow-sm border border-border flex flex-col items-center justify-center">
                        <span className="text-muted-foreground font-medium">
                            Draft
                        </span>
                        <span className="text-2xl font-bold text-foreground">
                            {safeStats.draft}
                        </span>
                    </div>

                    <div className="bg-card p-4 rounded-xl shadow-sm border border-border flex flex-col items-center justify-center">
                        <span className="text-muted-foreground font-medium">
                            Total Views
                        </span>
                        <span className="text-2xl font-bold text-foreground">
                            {safeStats.views}
                        </span>
                    </div>
                </div>

                {/* Filter Bar */}
                <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div className="relative w-full md:w-1/3 flex gap-2">
                        <Input
                            placeholder="Cari..."
                            value={searchTerm}
                            onChange={(e) => handleSearch(e.target.value)}
                            className="bg-card"
                        />
                        <Button
                            size="icon"
                            className="bg-primary hover:bg-primary/90 text-primary-foreground rounded-md shrink-0"
                        >
                            <Plus className="h-5 w-5" />
                        </Button>
                    </div>
                    <div className="w-full md:w-[180px]">
                        <Select
                            value={statusFilter}
                            onValueChange={handleFilterStatus}
                        >
                            <SelectTrigger className="bg-card">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua Status
                                </SelectItem>
                                <SelectItem value="published">
                                    Published
                                </SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Table Content */}
                <div className="bg-card rounded-xl shadow-sm border overflow-hidden">
                    <Table>
                        <TableHeader className="bg-muted">
                            <TableRow>
                                <TableHead className="w-[40%]">
                                    Berita
                                </TableHead>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>View</TableHead>
                                <TableHead>Rating</TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {berita.data.length > 0 ? (
                                berita.data.map((item) => (
                                    <TableRow
                                        key={item.id_berita}
                                        className="hover:bg-muted"
                                    >
                                        <TableCell>
                                            <div className="flex flex-col">
                                                <span className="font-bold text-foreground text-base">
                                                    {item.judul}
                                                </span>
                                                <span className="text-muted-foreground text-sm line-clamp-1">
                                                    {item.isi
                                                        .replace(
                                                            /<[^>]*>?/gm,
                                                            ""
                                                        )
                                                        .substring(0, 60)}
                                                    ...
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {new Date(
                                                item.tanggal
                                            ).toLocaleDateString("id-ID", {
                                                day: "2-digit",
                                                month: "short",
                                                year: "numeric",
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            {getStatusBadge(item.status)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1 text-muted-foreground text-sm">
                                                <Eye className="w-4 h-4" /> 0{" "}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1 text-muted-foreground text-sm">
                                                <Star className="w-4 h-4 text-muted-foreground" />{" "}
                                                0.0
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <button
                                                    onClick={() =>
                                                        openEdit(item)
                                                    }
                                                    className="p-2 hover:bg-accent  rounded-full transition-colors"
                                                >
                                                    <Pencil className="h-4 w-4 text-muted-foreground" />
                                                </button>
                                                <button
                                                    onClick={() =>
                                                        openDel(item)
                                                    }
                                                    className="p-2 hover:bg-destructive/10 rounded-full transition-colors"
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-center py-10 text-muted-foreground"
                                    >
                                        Tidak ada data berita.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                {berita.last_page > 1 && (
                    <div className="py-4">
                        <Pagination>
                            <PaginationContent>
                                {berita.links.map((link, i) => (
                                    <PaginationItem key={i}>
                                        <PaginationLink
                                            href={link.url}
                                            isActive={link.active}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    </PaginationItem>
                                ))}
                            </PaginationContent>
                        </Pagination>
                    </div>
                )}
            </div>

            <ChangeDialog
                open={openChange}
                onOpenChange={setOpenChange}
                data={selectedData}
            />
            <DeleteDialog
                open={openDelete}
                onOpenChange={setOpenDelete}
                data={selectedData}
            />
        </AppLayout>
    );
}
