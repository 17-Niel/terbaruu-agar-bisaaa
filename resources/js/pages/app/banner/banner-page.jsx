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
import { ChevronDown } from "lucide-react";
import * as React from "react";
import { toast } from "sonner";

// Import Dialogs
import { BannerChangeDialog } from "./dialogs/change-dialog";
import { BannerDeleteDialog } from "./dialogs/delete-dialog";

export default function BannerPage() {
    // ============================ DATA & STATE ============================

    // Ambil data dari server melalui Inertia
    const {
        bannerList,
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
    const titleChangeDialog = "Tambah Banner";

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
                route("banner") + `?page=${targetPage}&search=${debouncedSearch}`,
                debouncedSearch
            );
        }
    }, [debouncedSearch]);

    // Handle flash messages dan reload data
    React.useEffect(() => {
        if (flash?.success) {
            // Reload data
            handlePagination(route("banner") + `?page=1&perPage=${perPage}`, "");
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
    // Menggunakan logika yang sama dengan referensi untuk mengambil page param
    const paramPage =
        new URLSearchParams(window.location.search).get("page") || 1;

    const handlePagination = (page, search) => {
        setSearch(search);
        setRowSelection({});

        const url = new URL(page);
        const paramPerPage = url.searchParams.get("perPage") || perPage;

        const fixUrl =
            route("banner") +
            `?page=${paramPage}&perPage=${paramPerPage}&search=${search}`;

        router.visit(fixUrl, {
            preserveState: true,
            replace: true,
            only: ["bannerList"],
        });
    };

    // ============================ TABLE COLUMNS ============================

    let columns = [
        // Kolom seleksi baris
        {
            forEditor: true, // Flag khusus editor
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
        // Nomor Halaman (Index)
        {
            id: "No",
            header: "No",
            cell: ({ row }) => {
                return (
                    <div>
                        {(
                            (bannerList.current_page - 1) * bannerList.per_page +
                            row.index +
                            1
                        ).toString()}
                    </div>
                );
            },
            enableSorting: false,
        },
        // Kolom Nama Banner (BARU)
        {
            id: "Nama Banner",
            accessorKey: "nama_banner",
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() => {
                        column.toggleSorting(column.getIsSorted() === "asc");
                    }}
                >
                    Nama Banner
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
            cell: ({ row }) => <div className="pl-4 font-medium">{row.original.nama_banner}</div>,
        },
        // Kolom Urutan
        {
            id: "Urutan",
            accessorKey: "urutan",
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() => {
                        column.toggleSorting(column.getIsSorted() === "asc");
                    }}
                >
                    {column.id}
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
            cell: ({ row }) => <div className="pl-4">{row.original.urutan}</div>,
        },
        // Kolom Gambar (Preview & URL)
        {
            id: "Gambar",
            accessorKey: "url_gambar",
            header: "Gambar",
            cell: ({ row }) => (
                <div className="flex items-center space-x-3">
                    {/* Preview Gambar Kecil */}
                    <div className="h-10 w-16 overflow-hidden rounded bg-gray-100 border">
                        <img
                            src={row.original.url_gambar}
                            alt={`Banner ${row.original.nama_banner}`}
                            className="h-full w-full object-cover"
                            onError={(e) => { e.target.style.display = 'none'; }}
                        />
                    </div>
                </div>
            ),
        },
        // Kolom Status (Shown)
        {
            id: "Status",
            accessorKey: "shown",
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === "asc")
                    }
                >
                    Status
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
                <div>
                    {row.original.shown ? (
                        <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                            Ditampilkan
                        </span>
                    ) : (
                        <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                            Disembunyikan
                        </span>
                    )}
                </div>
            ),
        },
        // Kolom tindakan
        {
            forEditor: true, // Flag khusus editor
            id: "Tindakan",
            header: "Tindakan",
            isVisible: isEditor, // Kontrol visibilitas react-table
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
                            {/* Tombol ubah */}
                            <DropdownMenuItem
                                className="text-yellow-500"
                                onClick={() => {
                                    setDataEdit({
                                        bannerId: row.original.id_banner,
                                        nama_banner: row.original.nama_banner, // Kirim nama ke dialog edit
                                        urutan: row.original.urutan,
                                        url_gambar: row.original.url_gambar,
                                        shown: row.original.shown,
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
                            {/* Tombol hapus */}
                            <DropdownMenuItem
                                className="text-red-500"
                                onClick={() => {
                                    setDataDelete({
                                        bannerIds: [row.original.id_banner],
                                        dataList: [
                                            {
                                                id: row.original.id_banner,
                                                title: row.original.nama_banner, // Gunakan nama banner untuk konfirmasi hapus
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

    // Filter kolom berdasarkan hak akses (isEditor)
    if (!isEditor) {
        columns = columns.filter(
            (col) => !col.forEditor || col.forEditor === isEditor
        );
    }

    const table = useReactTable({
        data: bannerList.data,
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
        pageCount: bannerList.last_page,
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
                        {/* Judul Halaman */}
                        <div className="flex-1">
                            <div className="flex items-center">
                                <Icon.IconPictureInPictureFilled className="inline mr-2" />
                                <span>Daftar Banner</span>
                            </div>
                        </div>

                        {/* Toolbar */}
                        <div className="flex items-center space-x-2">
                            <InputGroup>
                                <InputGroupInput
                                    placeholder="Cari Nama Banner..."
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

                            {/* Tombol Tambah (Hanya Editor) */}
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
                    {/* Tombol Hapus Multiple */}
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
                                                (row) => row.original.id_banner
                                            );
                                        setDataDelete({
                                            bannerIds: selectedIds,
                                            dataList: table
                                                .getFilteredSelectedRowModel()
                                                .rows.map((row) => ({
                                                    id: row.original.id_banner,
                                                    title: row.original.nama_banner, // Info nama banner
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

                    {/* Tabel */}
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
                                                ? "Tidak ada banner yang sesuai dengan pencarian."
                                                : "Belum ada banner yang tersedia."}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
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
                                            route("banner") +
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
                                Menampilkan {bannerList.from} sampai{" "}
                                {bannerList.to} dari {bannerList.total}{" "}
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
                                        bannerList.prev_page_url,
                                        debouncedSearch
                                    )
                                }
                                disabled={!bannerList.prev_page_url}
                            >
                                Previous
                            </Button>

                            <span className="text-sm text-muted-foreground">
                                Halaman {bannerList.current_page} dari{" "}
                                {bannerList.last_page}
                            </span>

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePagination(
                                        bannerList.next_page_url,
                                        debouncedSearch
                                    )
                                }
                                disabled={!bannerList.next_page_url}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* ============================ DIALOGS ============================ */}

            <BannerChangeDialog
                dataEdit={dataEdit}
                dialogTitle={titleChangeDialog}
                openDialog={isChangeDialogOpen}
                setOpenDialog={setIsChangeDialogOpen}
            />

            <BannerDeleteDialog
                dataDelete={dataDelete}
                openDialog={isDeleteDialogOpen}
                setOpenDialog={setIsDeleteDialogOpen}
            />
        </AppLayout>
    );
}