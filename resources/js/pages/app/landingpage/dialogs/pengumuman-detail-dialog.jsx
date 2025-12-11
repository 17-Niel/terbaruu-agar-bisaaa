import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

export function PengumumanDetailDialog({ openDialog, setOpenDialog, dataDetail }) {
    if (!dataDetail) return null;

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    };

    return (
        <Dialog open={openDialog} onOpenChange={setOpenDialog}>
            <DialogContent className="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <div className="flex items-center gap-2 mb-2">
                        <span className="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs font-bold uppercase">
                            Pengumuman
                        </span>
                        <span className="text-gray-500 text-sm">
                            {formatDate(dataDetail.created_at)}
                        </span>
                    </div>
                    <DialogTitle className="text-2xl font-bold text-gray-900 leading-tight">
                        {dataDetail.judul}
                    </DialogTitle>
                </DialogHeader>

                <div className="mt-4 space-y-6">
                    {dataDetail.attachment_url && (
                        <div className="w-full rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                            {dataDetail.attachment.match(/\.(jpg|jpeg|png|gif|webp)$/i) ? (
                                <img src={dataDetail.attachment_url} alt={dataDetail.judul} className="w-full h-auto object-contain max-h-[400px]" />
                            ) : (
                                <div className="p-6 text-center">
                                    <a href={dataDetail.attachment_url} target="_blank" className="text-blue-600 font-bold hover:underline">Download Lampiran</a>
                                </div>
                            )}
                        </div>
                    )}
                    <div className="prose max-w-none text-gray-700 whitespace-pre-line">
                        {dataDetail.konten}
                    </div>
                </div>

                <DialogFooter className="mt-8">
                    <Button variant="outline" onClick={() => setOpenDialog(false)}>Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}