import 'package:flutter/material.dart';
import 'screens/public_attendance_screen.dart';

class AbsensiApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Absensi BKM',
      theme: ThemeData(primarySwatch: Colors.blue),
      initialRoute: '/',
      routes: {
        // Cukup sisakan satu rute utama ini saja
        '/': (_) => PublicAttendanceScreen(),
      },
    );
  }
}
