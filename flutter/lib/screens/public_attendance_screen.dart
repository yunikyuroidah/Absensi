import 'dart:convert';
import 'package:flutter/material.dart';
import '../core/api_service.dart';

class PublicAttendanceScreen extends StatefulWidget {
  @override
  _PublicAttendanceScreenState createState() => _PublicAttendanceScreenState();
}

class _PublicAttendanceScreenState extends State<PublicAttendanceScreen> {
  final _formKey = GlobalKey<FormState>();

  // Ganti Controller dengan variabel penampung ID yang dipilih
  String? _selectedEmployeeId;
  String _jenisAbsen = 'masuk';
  String? _statusKeterangan;

  final ApiService _api = ApiService();
  List<dynamic> _recent = [];
  List<dynamic> _employees = []; // Menampung daftar karyawan

  bool _loading = false;
  bool _loadingEmployees = true; // Indikator loading khusus dropdown

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);

    final payload = {
      'employee_id': _selectedEmployeeId, // Gunakan ID dari dropdown
      'jenis_absen': _jenisAbsen,
      'status': _statusKeterangan ?? '',
    };

    final res = await _api.postAttendance(payload);
    if (res.statusCode == 200 || res.statusCode == 201) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text('Absensi $_jenisAbsen berhasil dicatat!'),
        backgroundColor: Colors.green,
      ));

      // Reset form
      setState(() {
        _selectedEmployeeId = null; // Kosongkan dropdown
        _statusKeterangan = null;
      });
      _fetchRecent();
    } else {
      String msg = 'Gagal mengirim data: ${res.statusCode}';
      try {
        final body = json.decode(res.body);
        msg = body['message'] ?? msg;
      } catch (_) {}
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: Colors.red));
    }
    setState(() => _loading = false);
  }

  void _fetchInitialData() async {
    setState(() {
      _loading = true;
      _loadingEmployees = true;
    });

    try {
      // Tarik daftar absensi terbaru
      final rRecent = await _api.getAttendances();
      if (rRecent.statusCode == 200) {
        setState(
            () => _recent = json.decode(rRecent.body)['attendances'] ?? []);
      }

      // Tarik daftar karyawan untuk Dropdown
      final rEmp = await _api.getEmployees();
      if (rEmp.statusCode == 200) {
        final body = json.decode(rEmp.body);
        // Menyesuaikan dengan format response Laravel (bisa 'employees' atau 'data')
        setState(
            () => _employees = body['employees'] ?? body['data'] ?? body ?? []);
      }
    } catch (_) {}

    setState(() {
      _loading = false;
      _loadingEmployees = false;
    });
  }

  void _fetchRecent() async {
    try {
      final r = await _api.getAttendances();
      if (r.statusCode == 200) {
        setState(() => _recent = json.decode(r.body)['attendances'] ?? []);
      }
    } catch (_) {}
  }

  @override
  void initState() {
    super.initState();
    _fetchInitialData();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Absensi Karyawan BKM'),
        centerTitle: true,
        elevation: 0,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Form(
              key: _formKey,
              child: Column(
                children: [
                  // --- DROPDOWN KARYAWAN BARU ---
                  _loadingEmployees
                      ? Padding(
                          padding: const EdgeInsets.all(8.0),
                          child:
                              LinearProgressIndicator(), // Loading bar yang elegan
                        )
                      : DropdownButtonFormField<String>(
                          value: _selectedEmployeeId,
                          isExpanded:
                              true, // Mencegah teks nama yang terlalu panjang terpotong
                          items: _employees.map((emp) {
                            return DropdownMenuItem<String>(
                              value: emp['id'].toString(),
                              child: Text('${emp['nama']}  (ID: ${emp['id']})'),
                            );
                          }).toList(),
                          onChanged: (v) =>
                              setState(() => _selectedEmployeeId = v),
                          decoration: InputDecoration(
                            labelText: 'Nama Karyawan',
                            border: OutlineInputBorder(),
                            prefixIcon: Icon(Icons.person),
                          ),
                          validator: (v) =>
                              v == null ? 'Silakan pilih nama Anda' : null,
                        ),
                  // ------------------------------

                  SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: _jenisAbsen,
                    items: [
                      DropdownMenuItem(
                          value: 'masuk', child: Text('Absen Masuk')),
                      DropdownMenuItem(
                          value: 'keluar', child: Text('Absen Keluar')),
                    ],
                    onChanged: (v) => setState(() {
                      _jenisAbsen = v ?? 'masuk';
                      if (_jenisAbsen == 'keluar') {
                        _statusKeterangan = null;
                      }
                    }),
                    decoration: InputDecoration(
                      labelText: 'Jenis Absen',
                      border: OutlineInputBorder(),
                      prefixIcon: Icon(Icons.access_time),
                    ),
                  ),
                  SizedBox(height: 16),

                  if (_jenisAbsen == 'masuk')
                    DropdownButtonFormField<String?>(
                      value: _statusKeterangan,
                      items: [
                        DropdownMenuItem(
                            value: null, child: Text('Hadir (Otomatis)')),
                        DropdownMenuItem(value: 'izin', child: Text('Izin')),
                        DropdownMenuItem(value: 'sakit', child: Text('Sakit')),
                      ],
                      onChanged: (v) => setState(() => _statusKeterangan = v),
                      decoration: InputDecoration(
                        labelText: 'Keterangan (Opsional)',
                        hintText: 'Biarkan jika Hadir',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.info_outline),
                      ),
                    ),

                  if (_jenisAbsen == 'masuk') SizedBox(height: 16),

                  ElevatedButton(
                    onPressed: _loading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(vertical: 14),
                      minimumSize: Size(double.infinity, 50),
                    ),
                    child: _loading
                        ? SizedBox(
                            height: 24,
                            width: 24,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2))
                        : Text('Kirim Absensi', style: TextStyle(fontSize: 16)),
                  ),
                ],
              ),
            ),
            SizedBox(height: 24),
            Text('Riwayat Absensi Terkini',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            Divider(thickness: 1.5),
            Expanded(
              child: _loading && _recent.isEmpty
                  ? Center(child: CircularProgressIndicator())
                  : _recent.isEmpty
                      ? Center(child: Text('Belum ada data absensi hari ini.'))
                      : ListView.builder(
                          itemCount: _recent.length,
                          itemBuilder: (_, i) {
                            final it = _recent[i];
                            final isKeluar = it['jenis_absen'] == 'keluar' ||
                                it['jam_keluar'] != null;
                            final statusTeks =
                                it['status']?.toString().toUpperCase() ??
                                    'HADIR';

                            return Card(
                              margin: EdgeInsets.symmetric(vertical: 4),
                              child: ListTile(
                                leading: CircleAvatar(
                                  backgroundColor: isKeluar
                                      ? Colors.orange.shade100
                                      : Colors.green.shade100,
                                  child: Icon(
                                    isKeluar ? Icons.logout : Icons.login,
                                    color:
                                        isKeluar ? Colors.orange : Colors.green,
                                  ),
                                ),
                                title: Text(it['employee_name'] ??
                                    'Karyawan ID: ${it['employee_id']}'),
                                subtitle: Text(isKeluar
                                    ? 'KELUAR • ${it['jam_keluar'] ?? ''}'
                                    : '$statusTeks • ${it['jam_masuk'] ?? ''}'),
                              ),
                            );
                          },
                        ),
            ),
          ],
        ),
      ),
    );
  }
}
