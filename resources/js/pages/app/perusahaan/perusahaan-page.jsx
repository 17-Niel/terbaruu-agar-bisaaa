import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from "@/components/ui/input-group";
import {
    SelectContent,
    SelectItem,
    SelectTrigger,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
// Import komponen Dialog bawaan shadcn/ui untuk Detail Popup Inline
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Separator } from "@/components/ui/separator";

import AppLayout from "@/layouts/app-layout";
import { router, usePage } from "@inertiajs/react";
import { Select, SelectValue } from "@radix-ui/react-select";
import * as Icon from "@tabler/icons-react";
import {
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from "@tanstack/react-table";
import dayjs from "dayjs";
import { ChevronDown } from "lucide-react";
import * as React from "react";
import { toast } from "sonner";

// Import Dialogs (Eksternal)
import { PerusahaanChangeDialog } from "./dialogs/change-dialog";
import { PerusahaanDeleteDialog } from "./dialogs/delete-dialog";

export default function PerusahaanPage() {
    // ============================ DATA & STATE ============================

    const {
        perusahaanList,
        flash,
        isEditor,
        perPage,
        search: initialSearch,
        page: initialPage,
        perPageOptions,
    } = usePage().props;

    const [search, setSearch] = React.useState(initialSearch || "");
    const [debouncedSearch, setDebouncedSearch] = React.useState("");
    
    const titleChangeDialog = "Tambah Perusahaan";

    const [sorting, setSorting] = React.useState([]);
    const [columnFilters, setColumnFilters] = React.useState([]);
    const [columnVisibility, setColumnVisibility] = React.useState({});
    const [rowSelection, setRowSelection] = React.useState({});

    // State Dialog Edit/Tambah
    const [isChangeDialogOpen, setIsChangeDialogOpen] = React.useState(false);
    const [dataEdit, setDataEdit] = React.useState(null);

    // State Dialog Hapus
    const [dataDelete, setDataDelete] = React.useState(null);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = React.useState(false);

    // State Dialog Detail
    const [isDetailDialogOpen, setIsDetailDialogOpen] = React.useState(false);
    const [dataDetail, setDataDetail] = React.useState(null);

    const isFirst = React.useRef(true);

    // ============================ EFFECTS ============================

    React.useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(search);
        }, 500);
        return () => clearTimeout(timer);
    }, [search]);

    React.useEffect(() => {
        const targetPage = isFirst.current ? initialPage : 1;
        isFirst.current = false;

        if (debouncedSearch !== undefined) {
            handlePagination(
                route("perusahaan") + `?page=${targetPage}&search=${debouncedSearch}`,
                debouncedSearch
            );
        }
    }, [debouncedSearch]);

    React.useEffect(() => {
        if (flash.success) {
            handlePagination(route("perusahaan") + `?page=1&perPage=${perPage}`, "");
            setIsChangeDialogOpen(false);
            setIsDeleteDialogOpen(false);
            setIsDetailDialogOpen(false);
            setRowSelection({});
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    // ============================ FUNCTIONS ============================

    const handlePagination = (page, search) => {
        setSearch(search);
        setRowSelection({});

        const url = new URL(page);
        const paramPage = url.searchParams.get("page") || page;
        const paramPerPage = url.searchParams.get("perPage") || perPage;
        
        const fixUrl =
            route("perusahaan") +
            `?page=${paramPage}&perPage=${paramPerPage}&search=${search}`;

        router.visit(fixUrl, {
            preserveState: true,
            replace: true,
            only: ["perusahaanList"],
        });
    };

    // ============================ TABLE COLUMNS ============================

    let columns = [
        {
            forEditor: true,
            id: "Pilih Baris",
            header: ({ table }) => (
                <Checkbox
                    checked={
                        table.getIsAllPageRowsSelected() ||
                        (table.getIsSomePageRowsSelected() && "indeterminate")
                    }
                    onCheckedChange={(value) =>
                        table.toggleAllPageRowsSelected(!!value)
                    }
                    aria-label="Pilih semua baris"
                />
            ),
            cell: ({ row }) => (
                <Checkbox
                    checked={row.getIsSelected()}
                    onCheckedChange={(value) => row.toggleSelected(!!value)}
                    aria-label="Pilih baris"
                />
            ),
            enableSorting: false,
            enableHiding: false,
        },
        {
            id: "No",
            header: "No",
            cell: ({ row }) => {
                return (
                    <div>
                        {(
                            (perusahaanList.current_page - 1) * perusahaanList.per_page +
                            row.index +
                            1
                        ).toString()}
                    </div>
                );
            },
            enableSorting: false,
        },
        {
            id: "Nama Perusahaan",
            accessorKey: "nama", 
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() => {
                        column.toggleSorting(column.getIsSorted() === "asc");
                    }}
                >
                    Nama Perusahaan
                    {column.getIsSorted() ? (
                        column.getIsSorted() === "asc" ? (
                            <Icon.IconArrowUp size={16} />
                        ) : (
                            <Icon.IconArrowDown size={16} />
                        )
                    ) : (
                        <Icon.IconArrowsDownUp size={16} />
                    )}
                </Button>
            ),
            cell: ({ row }) => <div className="font-medium">{row.original.nama}</div>,
        },
        {
            id: "Industri",
            accessorKey: "industri",
            header: "Industri",
            cell: ({ row }) => <div>{row.original.industri || "-"}</div>,
        },
        {
            id: "Lokasi",
            accessorKey: "lokasi",
            header: "Lokasi",
            cell: ({ row }) => (
                <div className="max-w-[200px] truncate" title={row.original.lokasi}>
                    {row.original.lokasi || "-"}
                </div>
            ),
        },
        {
            id: "Website",
            accessorKey: "website",
            header: "Website",
            cell: ({ row }) => (
               row.original.website ? (
                   <a href={row.original.website} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">
                       Link
                   </a>
               ) : "-"
            ),
        },
        {
            id: "Terdaftar",
            accessorKey: "created_at",
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Terdaftar
                    {column.getIsSorted() ? (
                        column.getIsSorted() === "asc" ? (
                            <Icon.IconArrowUp size={16} />
                        ) : (
                            <Icon.IconArrowDown size={16} />
                        )
                    ) : (
                        <Icon.IconArrowsDownUp size={16} />
                    )}
                </Button>
            ),
            cell: ({ row }) => (
                <div className="text-left">
                    {dayjs(row.original.created_at).format("DD MMM YYYY")}
                </div>
            ),
        },
        {
            forEditor: true,
            id: "Tindakan",
            header: "Tindakan",
            isVisible: isEditor,
            cell: ({ row }) => {
                return (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Buka menu</span>
                                <Icon.IconDotsVertical />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            
                            <DropdownMenuItem
                                className="text-blue-500 cursor-pointer"
                                onClick={() => {
                                    setDataDetail(row.original);
                                    setIsDetailDialogOpen(true);
                                }}
                            >
                                <Icon.IconEye
                                    size={16}
                                    className="mr-2 text-blue-500"
                                />
                                Detail
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                className="text-yellow-500 cursor-pointer"
                                onClick={() => {
                                    setDataEdit({
                                        id_perusahaan: row.original.id_perusahaan,
                                        nama: row.original.nama,
                                        lokasi: row.original.lokasi,
                                        website: row.original.website,
                                        industri: row.original.industri,
                                        deskripsi: row.original.deskripsi,
                                        url_logo: row.original.url_logo,
                                    });
                                    setIsChangeDialogOpen(true);
                                }}
                            >
                                <Icon.IconPencil
                                    size={16}
                                    className="mr-2 text-yellow-500"
                                />
                                Ubah
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            
                            <DropdownMenuItem
                                className="text-red-500 cursor-pointer"
                                onClick={() => {
                                    setDataDelete({
                                        ids: [row.original.id_perusahaan], 
                                        dataList: [
                                            {
                                                id: row.original.id_perusahaan,
                                                title: row.original.nama,
                                            },
                                        ],
                                    });
                                    setIsDeleteDialogOpen(true);
                                }}
                            >
                                <Icon.IconTrash
                                    size={16}
                                    className="mr-2 text-red-500"
                                />
                                Hapus
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ];

    // ============================ TABLE CONFIG ============================

    if (!isEditor) {
        columns = columns.filter(
            (col) => !col.forEditor || col.forEditor === isEditor
        );
    }

    const table = useReactTable({
        data: perusahaanList.data, 
        columns,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        manualPagination: true,
        pageCount: perusahaanList.last_page,
        state: {
            sorting,
            columnFilters,
            columnVisibility,
            rowSelection,
        },
    });

    // ============================ RENDER ============================

    return (
        <AppLayout>
            <Card className="h-full">
                <CardHeader>
                    <CardTitle className="flex items-center">
                        <div className="flex-1">
                            <div className="flex items-center">
                                <Icon.IconBuilding className="inline mr-2" />
                                <span>Daftar Perusahaan</span>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <InputGroup>
                                <InputGroupInput
                                    placeholder="Cari Perusahaan..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                                <InputGroupAddon>
                                    <Icon.IconSearch />
                                </InputGroupAddon>
                            </InputGroup>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className="ml-auto"
                                    >
                                        Kolom <ChevronDown />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuCheckboxItem
                                        onSelect={(e) => e.preventDefault()}
                                        className="capitalize font-medium"
                                        checked={table
                                            .getAllColumns()
                                            .filter((col) => col.getCanHide())
                                            .every((col) => col.getIsVisible())}
                                        onCheckedChange={(value) => {
                                            table
                                                .getAllColumns()
                                                .filter((col) =>
                                                    col.getCanHide()
                                                )
                                                .forEach((col) =>
                                                    col.toggleVisibility(
                                                        !!value
                                                    )
                                                );
                                        }}
                                    >
                                        Pilih Semua
                                    </DropdownMenuCheckboxItem>
                                    {table
                                        .getAllColumns()
                                        .filter((column) => column.getCanHide())
                                        .map((column) => (
                                            <DropdownMenuCheckboxItem
                                                onSelect={(e) =>
                                                    e.preventDefault()
                                                }
                                                key={`column-toggle-${column.id}`}
                                                className="capitalize"
                                                checked={column.getIsVisible()}
                                                onCheckedChange={(value) =>
                                                    column.toggleVisibility(
                                                        !!value
                                                    )
                                                }
                                            >
                                                {column.id}
                                            </DropdownMenuCheckboxItem>
                                        ))}
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {isEditor && (
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setDataEdit(null);
                                        setIsChangeDialogOpen(true);
                                    }}
                                >
                                    <Icon.IconPlus />
                                </Button>
                            )}
                        </div>
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    {table.getFilteredSelectedRowModel().rows.length > 0 && (
                        <>
                            <div className="text-right mb-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="border-red-500 text-red-500 hover:bg-red-500 hover:text-white"
                                    onClick={() => {
                                        const selectedIds = table
                                            .getFilteredSelectedRowModel()
                                            .rows.map((row) => row.original.id_perusahaan);
                                    
                                        setDataDelete({
                                            ids: selectedIds, 
                                            dataList: table
                                                .getFilteredSelectedRowModel()
                                                .rows.map((row) => ({
                                                    id: row.original.id_perusahaan,
                                                    title: row.original.nama,
                                                })),
                                        });
                                        setIsDeleteDialogOpen(true);
                                    }}
                                >
                                    <Icon.IconTrash className="mr-2" />
                                    Hapus Semua yang Dipilih (
                                    {
                                        table.getFilteredSelectedRowModel().rows
                                            .length
                                    }
                                    )
                                </Button>
                            </div>
                        </>
                    )}

                    <div className="overflow-hidden rounded-md border">
                        <Table>
                            {/* [PERBAIKAN] Mengembalikan class bg-primary pada TableHeader */}
                            <TableHeader className="bg-primary">
                                {table.getHeaderGroups().map((headerGroup) => (
                                    <TableRow key={headerGroup.id}>
                                        {headerGroup.headers.map((header) => (
                                            <TableHead 
                                                key={header.id}
                                                // [PERBAIKAN] Mengembalikan class text-primary-foreground
                                                className="text-primary-foreground"
                                            >
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(
                                                          header.column
                                                              .columnDef.header,
                                                          header.getContext()
                                                      )}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                ))}
                            </TableHeader>
                            <TableBody>
                                {table.getRowModel().rows?.length ? (
                                    table.getRowModel().rows.map((row) => (
                                        <TableRow
                                            key={row.id}
                                            data-state={
                                                row.getIsSelected() &&
                                                "selected"
                                            }
                                        >
                                            {row
                                                .getVisibleCells()
                                                .map((cell) => (
                                                    <TableCell key={cell.id}>
                                                        {flexRender(
                                                            cell.column
                                                                .columnDef.cell,
                                                            cell.getContext()
                                                        )}
                                                    </TableCell>
                                                ))}
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={columns.length}
                                            className="h-24 text-center"
                                        >
                                            {search
                                                ? "Tidak ada data yang sesuai dengan pencarian."
                                                : "Belum ada data perusahaan."}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="flex items-center justify-between space-x-2 py-4">
                        <div className="flex-1">
                            <div className="flex items-center">
                                <label className="mr-2 text-sm text-muted-foreground">
                                    Data per halaman
                                </label>
                                <Select
                                    className="rounded border border-input bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={perPage}
                                    onValueChange={(perPage) => {
                                        handlePagination(
                                            route("perusahaan") +
                                                `?page=1&perPage=${perPage}`,
                                            debouncedSearch
                                        );
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {perPageOptions.map((size) => (
                                            <SelectItem
                                                key={size}
                                                value={size.toString()}
                                            >
                                                {size}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="text-muted-foreground text-sm">
                                Menampilkan {perusahaanList.from} sampai {perusahaanList.to}{" "}
                                dari {perusahaanList.total} data.
                                {table.getFilteredSelectedRowModel().rows
                                    .length > 0 && (
                                    <span className="ml-2">
                                        (
                                        {
                                            table.getFilteredSelectedRowModel()
                                                .rows.length
                                        }{" "}
                                        dipilih)
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePagination(
                                        perusahaanList.prev_page_url,
                                        debouncedSearch
                                    )
                                }
                                disabled={!perusahaanList.prev_page_url}
                            >
                                Previous
                            </Button>

                            <span className="text-sm text-muted-foreground">
                                Halaman {perusahaanList.current_page} dari{" "}
                                {perusahaanList.last_page}
                            </span>

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePagination(
                                        perusahaanList.next_page_url,
                                        debouncedSearch
                                    )
                                }
                                disabled={!perusahaanList.next_page_url}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* ============================ DIALOGS ============================ */}

            <PerusahaanChangeDialog
                dataEdit={dataEdit}
                dialogTitle={titleChangeDialog}
                openDialog={isChangeDialogOpen}
                setOpenDialog={setIsChangeDialogOpen}
            />

            <PerusahaanDeleteDialog
                dataDelete={dataDelete}
                openDialog={isDeleteDialogOpen}
                setOpenDialog={setIsDeleteDialogOpen}
            />

            {/* Detail Dialog (Inline & Putih Polos) */}
            <Dialog open={isDetailDialogOpen} onOpenChange={setIsDetailDialogOpen}>
                <DialogContent className="sm:max-w-[500px] bg-white p-0 overflow-hidden">
                    {dataDetail && (
                        <>
                            {/* Header Putih Polos */}
                            <DialogHeader className="p-6 pb-2 space-y-1 text-left">
                                <div className="flex items-start justify-between">
                                    <DialogTitle className="text-xl font-bold text-gray-900">
                                        {dataDetail.nama}
                                    </DialogTitle>
                                </div>
                                
                                {/* Badges Kategori */}
                                <div className="flex flex-wrap gap-2 mt-2">
                                    {dataDetail.industri && (
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            {dataDetail.industri}
                                        </span>
                                    )}
                                    {dataDetail.website && (
                                        <a 
                                            href={dataDetail.website} 
                                            target="_blank" 
                                            rel="noreferrer"
                                            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors"
                                        >
                                            <Icon.IconWorld size={14} className="mr-1" />
                                            Website
                                        </a>
                                    )}
                                </div>
                            </DialogHeader>

                            <div className="px-6 py-2">
                                {/* Logo Perusahaan (Tengah) */}
                                <div className="flex justify-center py-4">
                                    <div className="w-32 h-32 rounded-lg border bg-white flex items-center justify-center p-2 shadow-sm">
                                        {dataDetail.url_logo ? (
                                            <img
                                                src={dataDetail.url_logo}
                                                alt={`Logo ${dataDetail.nama}`}
                                                className="max-w-full max-h-full object-contain"
                                            />
                                        ) : (
                                            <Icon.IconBuilding size={48} className="text-gray-300" />
                                        )}
                                    </div>
                                </div>

                                {/* Informasi Detail */}
                                <div className="space-y-4 mt-2">
                                    {/* Lokasi */}
                                    <div>
                                        <h4 className="text-sm font-semibold text-blue-600 mb-1">
                                            Lokasi Perusahaan
                                        </h4>
                                        <div className="flex items-start text-sm text-gray-700">
                                            <Icon.IconMapPin size={16} className="mr-2 mt-0.5 text-gray-500" />
                                            <span>{dataDetail.lokasi || "-"}</span>
                                        </div>
                                    </div>

                                    {/* Industri */}
                                    <div>
                                        <h4 className="text-sm font-semibold text-blue-600 mb-1">
                                            Industri
                                        </h4>
                                        <div className="text-sm text-gray-700">
                                            {dataDetail.industri || "-"}
                                        </div>
                                    </div>

                                    {/* Deskripsi */}
                                    <div>
                                        <h4 className="text-sm font-semibold text-blue-600 mb-1">
                                            Deskripsi
                                        </h4>
                                        <p className="text-sm text-gray-700 leading-relaxed">
                                            {dataDetail.deskripsi || "Tidak ada deskripsi."}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Separator className="my-2" />

                            {/* Footer */}
                            <div className="px-6 pb-6 pt-2 flex items-center justify-between">
                                <div className="text-xs text-gray-500">
                                    Terdaftar: {dayjs(dataDetail.created_at).format("DD/MM/YYYY | HH:mm")}
                                </div>
                                <Button variant="outline" onClick={() => setIsDetailDialogOpen(false)}>
                                    Tutup
                                </Button>
                            </div>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}