import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import LandingLayout from '@/layouts/landing-layout'; 

// --- KOMPONEN KARTU PENGUMUMAN ---
const PengumumanCard = ({ item }) => {
    // Fungsi format tanggal manual (Tanpa library tambahan)
    const formatDate = (dateString) => {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
    };

    return (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-xl transition-shadow duration-300 flex flex-col h-full">
            {/* 1. Bagian Gambar */}
            <div className="h-52 w-full bg-gray-100 relative overflow-hidden group">
                {item.gambar_path ? (
                    <img 
                        // Mengakses file di folder storage/uploads/pengumuman
                        src={`/storage/${item.gambar_path}`} 
                        alt={item.judul} 
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        onError={(e) => { e.target.src = "https://via.placeholder.com/400x200?text=No+Image"; }} // Fallback jika gambar error
                    />
                ) : (
                    // Placeholder jika tidak ada gambar
                    <div className="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-slate-50">
                        <svg className="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                        </svg>
                        <span className="text-sm font-medium">Pengumuman</span>
                    </div>
                )}
                
                {/* Badge Tanggal Upload */}
                <div className="absolute top-3 right-3 bg-blue-600/90 backdrop-blur-sm text-white text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                    {formatDate(item.created_at)}
                </div>
            </div>

            {/* 2. Bagian Konten Teks */}
            <div className="p-5 flex flex-col flex-grow">
                <h3 className="text-xl font-bold text-slate-800 mb-3 line-clamp-2 leading-snug group-hover:text-blue-600 transition-colors">
                    {item.judul}
                </h3>
                
                <div className="text-gray-600 text-sm line-clamp-3 mb-4 flex-grow leading-relaxed text-justify">
                    {item.isi}
                </div>

                <div className="mt-auto pt-4 border-t border-gray-100 flex justify-between items-center text-xs">
                    <span className="text-gray-500">Berlaku sampai:</span>
                    <span className="text-red-500 font-semibold bg-red-50 px-2 py-1 rounded">
                        {formatDate(item.expired_date)}
                    </span>
                </div>
            </div>
        </div>
    );
};

// --- KOMPONEN UTAMA HALAMAN ---
export default function LandingPengumumanPage({ auth, contentData, state }) {
    const { data: pengumumanList, links } = contentData;

    // Handle Search
    const handleSearch = (e) => {
        e.preventDefault();
        const search = e.target.search.value;
        router.get(route('landing.pengumuman'), { search }, { preserveState: true });
    };

    return (
        <LandingLayout auth={auth} title="Pengumuman - Career Center" activeMenu="pengumuman">
            <Head title="Daftar Pengumuman" />

            {/* HEADER SECTION */}
            <div className="bg-gradient-to-b from-slate-50 to-white py-16 border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 className="text-3xl md:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">
                        Papan <span className="text-blue-600">Pengumuman</span>
                    </h1>
                    <p className="text-lg text-gray-600 max-w-2xl mx-auto mb-10">
                        Dapatkan informasi terbaru seputar akademik, kegiatan kemahasiswaan, dan berita penting lainnya dari Institut Teknologi Del.
                    </p>

                    {/* SEARCH BAR */}
                    <form onSubmit={handleSearch} className="max-w-xl mx-auto relative group">
                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg className="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="search"
                            defaultValue={state.search}
                            placeholder="Cari judul atau isi pengumuman..."
                            className="block w-full pl-11 pr-4 py-4 rounded-full border border-gray-300 bg-white text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-500 shadow-sm transition-all text-sm"
                        />
                        <button type="submit" className="absolute right-2 top-2 bottom-2 bg-slate-900 text-white px-6 rounded-full text-sm font-medium hover:bg-slate-800 transition-colors">
                            Cari
                        </button>
                    </form>
                </div>
            </div>

            {/* LIST PENGUMUMAN SECTION */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 min-h-[500px]">
                {pengumumanList.length > 0 ? (
                    <>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {pengumumanList.map((item) => (
                                <PengumumanCard key={item.id} item={item} />
                            ))}
                        </div>

                        {/* PAGINATION */}
                        <div className="mt-16 flex justify-center flex-wrap gap-2">
                            {links.map((link, k) => (
                                link.url ? (
                                    <Link
                                        key={k}
                                        href={link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                                            link.active
                                                ? 'bg-blue-600 text-white shadow-md ring-2 ring-blue-600 ring-offset-2'
                                                : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50 hover:text-blue-600'
                                        }`}
                                    />
                                ) : (
                                    <span
                                        key={k}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className="px-4 py-2 text-sm text-gray-400 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed opacity-60"
                                    />
                                )
                            ))}
                        </div>
                    </>
                ) : (
                    // TAMPILAN JIKA KOSONG
                    <div className="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-300 mx-auto max-w-3xl">
                        <div className="inline-flex p-4 rounded-full bg-blue-50 mb-4 ring-8 ring-blue-50/50">
                            <svg className="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 mb-2">Belum ada pengumuman</h3>
                        <p className="text-gray-500 max-w-md mx-auto">
                            {state.search 
                                ? `Tidak ditemukan pengumuman dengan kata kunci "${state.search}".` 
                                : "Saat ini belum ada pengumuman aktif yang ditampilkan."}
                        </p>
                        {state.search && (
                            <Link href={route('landing.pengumuman')} className="mt-6 inline-flex items-center text-blue-600 font-bold hover:underline">
                                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Reset Pencarian
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </LandingLayout>
    );
}