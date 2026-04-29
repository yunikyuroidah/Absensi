import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  final String baseUrl = 'http://127.0.0.1:8000/api';

  Future<http.Response> postAttendance(Map<String, dynamic> data) async {
    final url = Uri.parse('$baseUrl/attendances');
    return await http.post(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: json.encode(data),
    );
  }

  Future<http.Response> getAttendances() async {
    final url = Uri.parse('$baseUrl/mobile/bootstrap');
    return await http.get(
      url,
      headers: {'Accept': 'application/json'},
    );
  }

  // --- TAMBAHKAN FUNGSI INI ---
  Future<http.Response> getEmployees() async {
    final url = Uri.parse('$baseUrl/employees');
    return await http.get(
      url,
      headers: {'Accept': 'application/json'},
    );
  }
}
