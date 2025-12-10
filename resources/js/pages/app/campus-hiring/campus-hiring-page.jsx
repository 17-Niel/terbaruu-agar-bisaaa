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

// Import Dialogs (pastikan komponen dialog juga disesuaikan dengan nama baru)
import { CampusHiringChangeDialog } from "./dialogs/change-dialog";
import { CampusHiringDeleteDialog } from "./dialogs/delete-dialog";
import { CampusHiringDetailDialog } from "./dialogs/detail-dialog"; // detail-dialog bisa tetap menampilkan dataDetail

export default function CampusHiringPage() {
    // ============================ DATA & STATE ============================

    // Ambil data dari server melalui Inertia (sesuaikan keys dengan controller Laravel)
    const {
        campusHiringList,
        perusahaanList,
        jenisLowonganList, // Array of strings (['Full Time', 'Part Time', ...])
        flash,
        isEditor,
        perPage,
        search: initialSearch,
        page: initialPage,
        perPageOptions,
    } = usePage().props;

    // State untuk pencarian dengan debounce
    const [search, setSearch] = React.useState(initialSearch || "");
    const [debouncedSearch, setDebouncedSearch] = React.useState("");
    const titleChangeDialog = "Tambah Campus Hiring";

    // State untuk tabel
    const [sorting, setSorting] = React.useState([]);
    const [columnFilters, setColumnFilters] = React.useState([]);
    const [columnVisibility, setColumnVisibility] = React.useState({});
    const [rowSelection, setRowSelection] = React.useState({});

    // State untuk dialog edit
    const [isChangeDialogOpen, setIsChangeDialogOpen] = React.useState(false);
    const [dataEdit, setDataEdit] = React.useState(null);

    // State untuk dialog hapus
    const [dataDelete, setDataDelete] = React.useState(null);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = React.useState(false);

    // State untuk dialog detail
    const [isDetailDialogOpen, setIsDetailDialogOpen] = React.useState(false);
    const [dataDetail, setDataDetail] = React.useState(null);

    // Ref untuk menandai initial page load
    const isFirst = React.useRef(true);

    // ============================ EFFECTS ============================

    // Debounce untuk input pencarian
    React.useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(search);
        }, 500);

        return () => clearTimeout(timer);
    }, [search]);

    // Fetch data ketika pencarian berubah
    React.useEffect(() => {
        const targetPage = isFirst.current ? initialPage : 1;
        isFirst.current = false;

        if (debouncedSearch !== undefined) {
            handlePagination(
                route("campus-hiring") + `?page=${targetPage}&search=${debouncedSearch}`,
                debouncedSearch
            );
        }
    }, [debouncedSearch]);

    // Handle flash messages dan reload data
    React.useEffect(() => {
        if (flash?.success) {
            // Reload data
            handlePagination(route("campus-hiring") + `?page=1&perPage=${perPage}`, "");
            setIsChangeDialogOpen(false);
            setIsDeleteDialogOpen(false);
            setRowSelection({});
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    // ============================ FUNCTIONS ============================

    /**
     * Handle perubahan pagination
     */
    const paramPage =
    new URLSearchParams(window.location.search).get("page") || 1;
    const handlePagination = (page, search) => {
        setSearch(search);
        setRowSelection({});

        const url = new URL(page);
        const paramPerPage = url.searchParams.get("perPage") || perPage;

        const fixUrl =
            route("campus-hiring") +
            `?page=${paramPage}&perPage=${paramPerPage}&search=${search}`;

        router.visit(fixUrl, {
            preserveState: true,
            replace: true,
            only: ["campusHiringList"],
        });
    };

    // Helper: Mendapatkan nama perusahaan berdasarkan ID
    const getPerusahaanName = (id) => {
        const item = perusahaanList?.find((p) => p.id_perusahaan === id);
        return item ? item.nama : "Perusahaan Tidak Ditemukan";
    };

    // ============================ TABLE COLUMNS ============================

    let columns = [
        // Kolom seleksi baris
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
        // Nomor urut
        {
            id: "No",
            header: "No",
            cell: ({ row }) => {
                return (
                    <div>
                        {(
                            (campusHiringList.current_page - 1) *
                                campusHiringList.per_page +
                            row.index +
                            1
                        ).toString()}
                    </div>
                );
            },
            enableSorting: false,
        },
        // Kolom Nama Campus Hiring
        {
            id: "Nama",
            accessorKey: "nama_campus_hiring",
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() => {
                        column.toggleSorting(column.getIsSorted() === "asc");
                    }}
                >
                    Nama Campus Hiring
                    {column.getIsSorted() ? (
                        column.getIsSorted() === "asc" ? (
                            <Icon.IconArrowUp size={16} />
                        ) : (
                            <Icon.IconArrowDown size={16} />
                        )
                    ) : (
                        <Icon.IconArrowsDownUp className="ml-2 h-4 w-4" />
                    )}
                </Button>
            ),
            cell: ({ row }) => (
                <div className="font-medium">{row.original.nama_campus_hiring}</div>
            ),
        },

        // Kolom Departemen (jika ada)
        {
            id: "Departemen",
            accessorKey: "departemen",
            header: "Departemen",
            cell: ({ row }) => (
                <div className="text-muted-foreground text-sm">
                    {row.original.departemen || "-"}
                </div>
            ),
        },

        // Kolom Perusahaan
        {
            id: "Perusahaan",
            accessorKey: "id_perusahaan",
            header: "Perusahaan",
            cell: ({ row }) => (
                <div>{getPerusahaanName(row.original.id_perusahaan)}</div>
            ),
        },

        // Kolom Batas Akhir
        {
            id: "Batas Akhir",
            accessorKey: "batas_akhir",
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Batas Akhir
                    {column.getIsSorted() ? (
                        column.getIsSorted() === "asc" ? (
                            <Icon.IconArrowUp size={16} />
                        ) : (
                            <Icon.IconArrowDown size={16} />
                        )
                    ) : (
                        <Icon.IconArrowsDownUp className="ml-2 h-4 w-4" />
                    )}
                </Button>
            ),
            cell: ({ row }) => (
                <div className="text-left">
                    {row.original.batas_akhir ? (
                        dayjs(row.original.batas_akhir).format("DD MMM YYYY")
                    ) : (
                        <span className="text-xs italic text-muted-foreground">
                            Secepatnya
                        </span>
                    )}
                </div>
            ),
        },

        // Kolom tindakan
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
                            {/* Tombol DETAIL */}
                            <DropdownMenuItem
                                className="text-blue-500"
                                onClick={() => {
                                    setDataDetail({
                                        ...row.original,
                                        nama_perusahaan: getPerusahaanName(
                                            row.original.id_perusahaan
                                        ),
                                        nama_jenis_lowongan:
                                            row.original.jenis_lowongan || "-",
                                    });
                                    setIsDetailDialogOpen(true);
                                }}
                            >
                                <Icon.IconEye
                                    size={16}
                                    className="mr-2 text-blue-500"
                                />
                                Detail
                            </DropdownMenuItem>

                            {/* Tombol UBAH */}
                            <DropdownMenuItem
                                className="text-yellow-500"
                                onClick={() => {
                                    setDataEdit({
                                        id_campus_hiring: row.original.id_campus_hiring,
                                        id_perusahaan: row.original.id_perusahaan,
                                        jenis_lowongan: row.original.jenis_lowongan,
                                        nama_campus_hiring: row.original.nama_campus_hiring,
                                        departemen: row.original.departemen,
                                        deskripsi: row.original.deskripsi,
                                        kualifikasi: row.original.kualifikasi,
                                        benefit: row.original.benefit,
                                        kualifikasi_pendidikan: row.original.kualifikasi_pendidikan,
                                        batas_akhir: row.original.batas_akhir,
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

                            <DropdownMenuItem
                                className="text-green-500"
                                onClick={() => {
                                    // PERBAIKAN: Membuka route download di tab baru
                                    const url = route('campus-hiring.download', { id: row.original.id_campus_hiring });
                                    window.open(url, '_blank');
                                }}
                            >
                                <Icon.IconDownload
                                    size={16}
                                    className="mr-2 text-green-500"
                                />
                                Download
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />

                            {/* Tombol HAPUS */}
                            <DropdownMenuItem
                                className="text-red-500"
                                onClick={() => {
                                    setDataDelete({
                                        ids_campus_hiring: [row.original.id_campus_hiring],
                                        dataList: [
                                            {
                                                id: row.original.id_campus_hiring,
                                                title: row.original.nama_campus_hiring,
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
        data: campusHiringList.data,
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
        pageCount: campusHiringList.last_page,
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
                                <Icon.IconBriefcase className="inline mr-2" />
                                <span>Daftar Campus Hiring</span>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <InputGroup>
                                <InputGroupInput
                                    placeholder="Cari..."
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
                                        Kolom{" "}
                                        <ChevronDown className="ml-1 h-4 w-4" />
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
                                            .rows.map(
                                                (row) =>
                                                    row.original.id_campus_hiring
                                            );
                                        setDataDelete({
                                            ids_campus_hiring: selectedIds,
                                            dataList: table
                                                .getFilteredSelectedRowModel()
                                                .rows.map((row) => ({
                                                    id: row.original.id_campus_hiring,
                                                    title: row.original
                                                        .nama_campus_hiring,
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
                            <TableHeader className="bg-primary">
                                {table.getHeaderGroups().map((headerGroup) => (
                                    <TableRow key={headerGroup.id}>
                                        {headerGroup.headers.map((header) => (
                                            <TableHead
                                                key={header.id}
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
                                                : "Belum ada data Campus Hiring."}
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
                                    value={perPage.toString()}
                                    onValueChange={(val) => {
                                        handlePagination(
                                            route("campus-hiring") +
                                                `?page=1&perPage=${val}`,
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
                                Menampilkan {campusHiringList.from} sampai{" "}
                                {campusHiringList.to} dari {campusHiringList.total}{" "}
                                data.
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
                                        campusHiringList.prev_page_url,
                                        debouncedSearch
                                    )
                                }
                                disabled={!campusHiringList.prev_page_url}
                            >
                                Previous
                            </Button>

                            <span className="text-sm text-muted-foreground">
                                Halaman {campusHiringList.current_page} dari{" "}
                                {campusHiringList.last_page}
                            </span>

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePagination(
                                        campusHiringList.next_page_url,
                                        debouncedSearch
                                    )
                                }
                                disabled={!campusHiringList.next_page_url}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* ============================ DIALOGS ============================ */}

            {/* Dialog ubah/tambah campus hiring */}
            <CampusHiringChangeDialog
                dataEdit={dataEdit}
                dialogTitle={titleChangeDialog}
                openDialog={isChangeDialogOpen}
                setOpenDialog={setIsChangeDialogOpen}
                // Meneruskan list string ke dialog
                jenisLowonganList={jenisLowonganList}
                perusahaanList={perusahaanList}
            />

            {/* Dialog hapus campus hiring */}
            <CampusHiringDeleteDialog
                dataDelete={dataDelete}
                openDialog={isDeleteDialogOpen}
                setOpenDialog={setIsDeleteDialogOpen}
            />

            {/* Dialog Detail Campus Hiring */}
            <CampusHiringDetailDialog
                openDialog={isDetailDialogOpen}
                setOpenDialog={setIsDetailDialogOpen}
                dataDetail={dataDetail}
            />
        </AppLayout>
    );
}