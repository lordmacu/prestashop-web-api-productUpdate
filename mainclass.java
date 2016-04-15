package coneccion;

import com.healthmarketscience.jackcess.Database;
import com.healthmarketscience.jackcess.DatabaseBuilder;
import com.healthmarketscience.jackcess.Row;
import com.healthmarketscience.jackcess.Table;

import net.ucanaccess.complex.Attachment;
import javax.swing.JFileChooser;

import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Container;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Image;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.*;
import java.util.Properties;
import java.util.Scanner;

import javax.imageio.ImageIO;
import javax.swing.BorderFactory;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JMenu;
import javax.swing.JMenuBar;
import javax.swing.JMenuItem;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JProgressBar;
import javax.swing.SwingConstants;
import javax.swing.border.Border;
import javax.swing.filechooser.FileNameExtensionFilter;

import org.apache.commons.io.FileUtils;

/**
 *
 * @author cramirez
 */
public class mainclass extends JFrame implements ActionListener, java.io.Serializable {

	private static Connection con;
	private static Statement st;

	private static ResultSet rs;
	static HttpURLConnection conn = null;
	BufferedReader br = null;
	static DataOutputStream dos = null;
	static DataInputStream inStream = null;

	private JMenuBar mb;
	private JMenu menu1;
	private JMenuItem mi1, mi2, mi3;

	InputStream is = null;
	OutputStream os = null;
	boolean ret = false;
	String StrMessage = "";
	static String exsistingFileName = "C:\\temp\\screenCapture_20110413_052404.GIF";
	static String nombre = "ombre";
	static String lineEnd = "\r\n";
	static String twoHyphens = "--";
	static String boundary = "*****";
	static int totalregistros = 0;
	static int procesados = 0;
	static String palabrasClave = "";
	static String referencia_producto = "";
	static String descripcion_producto = "";
	static String precio_producto = "";
	static String cantidad_producto = "";
	static String id_marca = "";
	static String codigo_proveedor = "";
	static transient String rutaarchivo;
	static String codigo_producto="";
	static JLabel label = new JLabel(" ");;
	static JLabel baseDeDatos = new JLabel();;
	static JLabel labelrespuesta = new JLabel();;

	
	static String activo_tienda;
	static JProgressBar progressBar;
	static JButton button = new JButton();
	static String categoria_padre="";
	static String categoria_hijo="";
    static Image icon;
    static String imag_ft;
    static String creado_prestashop="";
    static String descripcion_corta="";
    static String link_imagen_principal;
	/**
	 * @param args
	 *            the command line arguments
	 * @throws SQLException
	 * @throws IOException
	 * @throws ClassNotFoundException
	 */
	public static void main(String[] args) throws SQLException, IOException, ClassNotFoundException {
		mainclass formulario1 = new mainclass();
		formulario1.setBounds(10, 20, 345, 230);
		formulario1.setVisible(true);
	}

	public class process extends Thread {

		@Override
		public void run() {
			try {
				process();
			} catch (SQLException | IOException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			} catch (InterruptedException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}

		}
	}

	public class progressDbNueva extends Thread {

		@Override
		public void run() {
			try {
				button.setText("Procesando base de datos");

				totalRegistros();
			} catch (ClassNotFoundException | SQLException e) {
				e.printStackTrace();
			}

		}

	}
	
	
	public void escribirRutaBase(String ruta) throws IOException{
		
		
		
		 String text = ruta;
	        BufferedWriter output = null;
	        try {
				File desktop = new File(System.getProperty("user.home"));
	            File file = new File(desktop+"/rutabase.txt");
	            if (file.exists()) {
	            	file.delete();     
	             }
	            output = new BufferedWriter(new FileWriter(file));
	            output.write(text);
	            String data = FileUtils.readFileToString(new File(desktop+"/rutabase.txt"), "UTF-8");
	        } catch ( IOException e ) {
	            e.printStackTrace();
	        } finally {
	            if ( output != null ) output.close();
	        }
	}

	public class progressDb extends Thread {

		@Override
		public void run() {
			File f=null;
			File desktop = new File(System.getProperty("user.home"));
			File rutaFile = new File(desktop + "/rutabase.txt");
			if (rutaFile.exists() && !rutaFile.isDirectory()) {
				String data;
				try {
					data = FileUtils.readFileToString(new File(desktop + "/rutabase.txt"), "UTF-8");
					rutaarchivo = data;
					System.out.println("eixte el archivo txt "+desktop + "/rutabase.txt "+data);
					 f = new File(data);
				} catch (IOException e) {
					e.printStackTrace();
				}
			}else{
				try {
					 escribirRutaBase(desktop + "/mctools_db.accdb");
					 f = new File(desktop + "/mctools_db.accd");
					 rutaarchivo = desktop + "/mctools_db.accdb";
				} catch (IOException e) {
					e.printStackTrace();
				}
			}
			if (f.exists() && !f.isDirectory()) {
				baseDeDatos.setText(rutaarchivo);
				try {
					escribirRutaBase(rutaarchivo);
				} catch (IOException e1) {
					e1.printStackTrace();
				}
				try {
					totalRegistros();
				} catch (ClassNotFoundException | SQLException e) {
					e.printStackTrace();
				}
			} else {
				button.setText("No hay base de datos");
				baseDeDatos.setText(" Seleccione la base de datos en opciones.");
			}

		}

	}

	public mainclass() throws ClassNotFoundException, SQLException, IOException {
		
		
		
		setLayout(null);
		try {
		     ClassLoader cl = this.getClass().getClassLoader();
		     Image image = Toolkit.getDefaultToolkit().createImage("resource/icon_gears.png");   //Image for Your Panel
		     setIconImage(image);
		  } catch (Exception whoJackedMyIcon) {
		     System.out.println("Could not load program icon.");
		  }
	
		mb = new JMenuBar();
		setJMenuBar(mb);
		menu1 = new JMenu("Opciones");
		mb.add(menu1);
		mi1 = new JMenuItem("Seleccione la base de datos");
		mi1.addActionListener(this);
		menu1.add(mi1);
		ClickListener cl = new ClickListener();

		final Dimension size = label.getPreferredSize();
		label.setMinimumSize(size);
		label.setPreferredSize(size);
		label.setBounds(15, 10, 300, 30);
		add(label);

		button.setText("Comenzar proceso");
		button.setBounds(15, 50, 300, 30);
		button.addActionListener(cl);

		button.setText("Verificando Datos...");
		button.setEnabled(false);
		add(button);

		progressBar = new JProgressBar();
		progressBar.setValue(0);
		progressBar.setStringPainted(true);
		progressBar.setBounds(15, 90, 300, 30);
		add(progressBar, BorderLayout.NORTH);
		Border border = BorderFactory.createTitledBorder("Proceso");
		progressBar.setBorder(border);
		
		baseDeDatos.setMinimumSize(size);
		baseDeDatos.setPreferredSize(size);
		baseDeDatos.setBounds(15, 120, 300, 30);
		add(baseDeDatos);

		labelrespuesta.setMinimumSize(size);
		labelrespuesta.setForeground(Color.gray);
		labelrespuesta.setPreferredSize(size);
		labelrespuesta.setBounds(15, 140, 300, 30);
		labelrespuesta.setHorizontalAlignment(SwingConstants.RIGHT);
		add(labelrespuesta); 
		setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
		progressDb trProgressDb = new progressDb();
		trProgressDb.start();

	}

	private class ClickListener implements ActionListener {
		public void actionPerformed(ActionEvent e) {
			if (e.getSource() == button) {
				process t3 = new process();
				t3.start();
				button.setText("Procesando...");
				 button.setEnabled(false);
			}
		}
	}

	public static void updateBar(int newValue) {
		progressBar.setValue(newValue);
		
		if(newValue==100){
			button.setEnabled(true);
			button.setText("Comenzar proceso");
			JOptionPane.showMessageDialog(null, "Se terminó el proceso con éxito.", "alert", JOptionPane.PLAIN_MESSAGE);

		}
	}

	
	
	public static void actualizarRegistro(String codigo_producto, String creado_prestashop2) throws ClassNotFoundException, SQLException {
		Class.forName("net.ucanaccess.jdbc.UcanaccessDriver");
		Connection conn = DriverManager.getConnection("jdbc:ucanaccess://" + rutaarchivo);
		Statement st;
		int rs;
		conn.setAutoCommit(false); //Notice change here

		st = conn.createStatement();
		rs = st.executeUpdate("UPDATE t_producto SET registro_publicar='0',creado_prestashop='1' where codigo_producto = '" + codigo_producto + "'");
		conn.commit();
		System.err.println(rs);
		st.close();
		conn.close();
	}
	
	
	public static void actualizarRegistroImagen(String codigo_producto) throws ClassNotFoundException, SQLException {
		Class.forName("net.ucanaccess.jdbc.UcanaccessDriver");
		Connection conn = DriverManager.getConnection("jdbc:ucanaccess://" + rutaarchivo);
		Statement st;
		int rs;
		st = conn.createStatement();
		rs = st.executeUpdate("UPDATE t_producto SET imagen_exportar='0' where codigo_producto = '" + codigo_producto + "'");
		System.err.println(rs);
		st.close();
		conn.close();
	}
	
	
	public static void totalRegistros() throws ClassNotFoundException, SQLException {
		Class.forName("net.ucanaccess.jdbc.UcanaccessDriver");
		Connection conn = DriverManager.getConnection("jdbc:ucanaccess://" + rutaarchivo);
		Statement st;
		ResultSet rs;
		st = conn.createStatement();
		rs = st.executeQuery("SELECT COUNT(*) AS total FROM t_producto where registro_publicar=1");
		while (rs.next()) {
			System.err.println(rs.getString("total"));
			totalregistros = Integer.parseInt(rs.getString("total"));
		}
		
		if(totalregistros==0){
			JOptionPane.showMessageDialog(null, "No hay registros para publicar", "alert", JOptionPane.ERROR_MESSAGE);
			button.setText("Comenzar proceso");

			button.setEnabled(true);
		}else{
			button.setText("Procesar");
			button.setEnabled(true);
		}
		st.close();
		conn.close();
	
	}

	public static String getKeywords(String palabras) throws  ClassNotFoundException {
		String[] split = palabras.split(",");
		String categorias = "";
		for (int i = 0; i < split.length; i++) {
			categorias = categorias + "," + split[i].trim();
		}
		return categorias;
	}
	
	public static String getKeywordsd(String codigo) throws SQLException, ClassNotFoundException {
		Statement st;
		ResultSet rs;
		Class.forName("net.ucanaccess.jdbc.UcanaccessDriver");
		Connection conn = DriverManager.getConnection("jdbc:ucanaccess://" + rutaarchivo);
		st = conn.createStatement();
		rs = st.executeQuery("select * from t_palabras_clave where codigo_producto = '" + codigo + "'");
		String categorias = "";
		while (rs.next()) {
			categorias = categorias + "," + rs.getString("palabra_clave");
		}
		st.close();
		conn.close();
		return categorias;
	}

	public static void process() throws SQLException, IOException, InterruptedException {
		try {
			Class.forName("net.ucanaccess.jdbc.UcanaccessDriver");
			Connection conn = DriverManager.getConnection("jdbc:ucanaccess://" + rutaarchivo);
			st = conn.createStatement();
			rs = st.executeQuery("select * from t_producto  where registro_publicar=1 ");
			File desktop = new File(System.getProperty("user.home"));
			int contador=0;
			while (rs.next()) {
				exsistingFileName = "no";
				nombre = "";
				
				/*if ( Integer.parseInt(rs.getString("imagen_exportar")) ==1) {
					Attachment[] atts = (Attachment[]) rs.getObject("imagen_producto");
					for (Attachment att : atts) {
						exsistingFileName = desktop + "/" + att.getName();

						System.err.println(exsistingFileName);

						FileOutputStream fos = new FileOutputStream(desktop + "/" + att.getName());
						

						fos.write(att.getData());
						fos.close();
					
					}
				}*/
				if (rs.getString("nombre_producto") != null) {
					nombre = rs.getString("nombre_producto").trim();
				}
				if (rs.getString("referencia_producto") != null) {
					referencia_producto = rs.getString("referencia_producto").trim();
				}
				if (rs.getString("descripcion_producto") != null) {
					descripcion_producto = rs.getString("descripcion_producto").trim();
				}
				if (rs.getString("precio_producto") != null) {
					precio_producto = rs.getString("precio_producto").trim();
				}
				if (rs.getString("precio_producto") != null) {
					precio_producto = rs.getString("precio_producto").trim();
				}
				if (rs.getString("cantidad_producto") != null) {
					cantidad_producto = rs.getString("cantidad_producto").trim();
				}
				if (rs.getString("palabras_clave") != null) {
					//palabrasClave = getKeywordsd(rs.getString("codigo_producto").trim());
					palabrasClave = getKeywords(rs.getString("palabras_clave").trim());

				}
				if (rs.getString("id_marca") != null) {
					id_marca = rs.getString("id_marca").trim();
				}
				if (rs.getString("codigo_producto") != null) {
					codigo_producto = rs.getString("codigo_producto").trim();
				}
				if (rs.getString("codigo_proveedor") != null) {
					codigo_proveedor = rs.getString("codigo_proveedor").trim();
				}
				
				if (rs.getString("activo_tienda") != null) {
					activo_tienda = rs.getString("activo_tienda").trim();
				}
				
				if (rs.getString("categoria_prestashop") != null) {
					categoria_padre = rs.getString("categoria_prestashop").trim();
				}
				if (rs.getString("grupo_prestashop") != null) {
					categoria_hijo = rs.getString("grupo_prestashop").trim();
				}
				
				if (rs.getString("imag_ft") != null) {
					imag_ft = rs.getString("imag_ft").trim();
				}
				
				if (rs.getString("link_imagen_principal") != null) {
					link_imagen_principal = rs.getString("link_imagen_principal").trim();
				}
				
				
				if (rs.getString("creado_prestashop") != null) {
					creado_prestashop = rs.getString("creado_prestashop").trim();
				}
				
				if (rs.getString("descripcion_corta") != null) {
					descripcion_corta = rs.getString("descripcion_corta").trim();
				}
				
				
				
				System.err.println("esta es la desc corta "+descripcion_corta);

				
				if (nombre != null) {
					label.setText(nombre);
					upload(nombre, exsistingFileName, referencia_producto, descripcion_producto, precio_producto,
							cantidad_producto, palabrasClave, id_marca, codigo_proveedor,codigo_producto,categoria_padre,categoria_hijo,imag_ft,creado_prestashop,descripcion_corta,link_imagen_principal);
				}
				procesados++;
				int primertotal = procesados * 100;
				updateBar(primertotal / totalregistros);
				Thread.sleep(1000);
				contador++;
			}
			
			if(contador==0){
				JOptionPane.showMessageDialog(null, "No hay registros para publicar", "alert", JOptionPane.ERROR_MESSAGE);
				button.setText("Comenzar proceso");
				button.setEnabled(true);

			}
			st.close();
			conn.close();
		} catch (ClassNotFoundException e) {
			System.err.println("Got an exception! ");
			System.err.println(e.getMessage());
		}
	}

	public static void upload(String name, String exsistingFileName, String referencia_producto,
			String descripcion_producto, String precio_producto, String cantidad_producto, String palabrasClave,
			String id_marca, String codigo_proveedor,String codigo_producto, String categoria_padre2, String categoria_hijo2, String imag_ft, String creado_prestashop2, String descripcion_corta2, String link_imagen_principal) throws ClassNotFoundException, SQLException {

		int bytesRead, bytesAvailable, bufferSize;

		byte[] buffer;

		int maxBufferSize = 1 * 1024 * 1024;

		String urlString = "http://request.bogotac.com/";

		try {
			labelrespuesta.setText(" ");

			// ------------------ CLIENT REQUEST

			FileInputStream fileInputStream = null;

			if (exsistingFileName != "no") {
				fileInputStream = new FileInputStream(new File(exsistingFileName));
			}

			URL url = new URL(urlString);

			conn = (HttpURLConnection) url.openConnection();

			conn.setDoInput(true);

			conn.setDoOutput(true);

			conn.setUseCaches(false);

			conn.setRequestMethod("POST");

			conn.setRequestProperty("Connection", "Keep-Alive");

			conn.setRequestProperty("Content-Type", "multipart/form-data;boundary=" + boundary);

			dos = new DataOutputStream(conn.getOutputStream());
			//// categoria_padre2
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"categoria_padre\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(categoria_padre2);
			dos.writeBytes(lineEnd);
			//// categoria_padre2
			
		//// categoria_padre2
					dos.writeBytes(twoHyphens + boundary + lineEnd);
					dos.writeBytes("Content-Disposition: form-data; name=\"categoria_padre\"" + lineEnd);
					dos.writeBytes(lineEnd);
					dos.writeBytes(categoria_padre2);
					dos.writeBytes(lineEnd);
					//// categoria_padre2
			
					
					if(imag_ft!=null){
						//// imag_ft
						dos.writeBytes(twoHyphens + boundary + lineEnd);
						dos.writeBytes("Content-Disposition: form-data; name=\"imag_ft\"" + lineEnd);
						dos.writeBytes(lineEnd);
						dos.writeBytes(imag_ft);
						dos.writeBytes(lineEnd);
						//// imag_ft
					}
					if(link_imagen_principal!=null){
						//// link_imagen_principal
						dos.writeBytes(twoHyphens + boundary + lineEnd);
						dos.writeBytes("Content-Disposition: form-data; name=\"link_imagen_principal\"" + lineEnd);
						dos.writeBytes(lineEnd);
						dos.writeBytes(link_imagen_principal);
						dos.writeBytes(lineEnd);
						//// link_imagen_principal
					}
				
					
					
					
				//// descripcion_corta2
									dos.writeBytes(twoHyphens + boundary + lineEnd);
									dos.writeBytes("Content-Disposition: form-data; name=\"descripcion_corta\"" + lineEnd);
									dos.writeBytes(lineEnd);
									dos.writeBytes(descripcion_corta2);
									dos.writeBytes(lineEnd);
									//// categoria_hijo2
								//// descripcion_corta2
					
					
		//// categoria_hijo2
					dos.writeBytes(twoHyphens + boundary + lineEnd);
					dos.writeBytes("Content-Disposition: form-data; name=\"categoria_hijo\"" + lineEnd);
					dos.writeBytes(lineEnd);
					dos.writeBytes(categoria_hijo2);
					dos.writeBytes(lineEnd);
					//// categoria_hijo2
				//// nombre
									dos.writeBytes(twoHyphens + boundary + lineEnd);
									dos.writeBytes("Content-Disposition: form-data; name=\"title\"" + lineEnd);
									dos.writeBytes(lineEnd);
									dos.writeBytes(name);
									dos.writeBytes(lineEnd);
									//// nombre

			//// referencia_producto
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"referencia_producto\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(referencia_producto);
			dos.writeBytes(lineEnd);
			//// referencia_producto

			//// descripcion_producto
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"descripcion_producto\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(descripcion_producto);
			dos.writeBytes(lineEnd);
			//// descripcion_producto

			//// precio_producto
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"precio_producto\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(precio_producto);
			dos.writeBytes(lineEnd);
			//// precio_producto
			
			
			//// activo_tienda
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"activo_tienda\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(activo_tienda);
			dos.writeBytes(lineEnd);
			//// activo_tienda
			
			
			//// codigo_producto
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"codigo_producto\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(codigo_producto);
			dos.writeBytes(lineEnd);
			//// codigo_producto
			

			//// cantidad_producto
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"cantidad_producto\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(cantidad_producto);
			dos.writeBytes(lineEnd);
			//// cantidad_producto

			//// palabrasClave
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"palabrasClave\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(palabrasClave);
			dos.writeBytes(lineEnd);
			//// palabrasClave

			//// id_marca
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"id_marca\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(id_marca);
			dos.writeBytes(lineEnd);
			//// id_marca

			//// codigo_proveedor
			dos.writeBytes(twoHyphens + boundary + lineEnd);
			dos.writeBytes("Content-Disposition: form-data; name=\"codigo_proveedor\"" + lineEnd);
			dos.writeBytes(lineEnd);
			dos.writeBytes(codigo_proveedor);
			dos.writeBytes(lineEnd);
			//// codigo_proveedor

			if (exsistingFileName != "no") {

				dos.writeBytes(twoHyphens + boundary + lineEnd);
				dos.writeBytes("Content-Disposition: form-data; name=\"upload\";" + " filename=\"" + exsistingFileName
						+ "\"" + lineEnd);
				dos.writeBytes(lineEnd);
				
				bytesAvailable = fileInputStream.available();
				bufferSize = Math.min(bytesAvailable, maxBufferSize);
				buffer = new byte[bufferSize];
				bytesRead = fileInputStream.read(buffer, 0, bufferSize);

				while (bytesRead > 0) {
					dos.write(buffer, 0, bufferSize);
					bytesAvailable = fileInputStream.available();
					bufferSize = Math.min(bytesAvailable, maxBufferSize);
					bytesRead = fileInputStream.read(buffer, 0, bufferSize);
				}

				dos.writeBytes(lineEnd);
				dos.writeBytes(twoHyphens + boundary + twoHyphens + lineEnd);
				fileInputStream.close();

			}

			dos.flush();

			dos.close();

		} catch (MalformedURLException ex) {
			System.out.println("From ServletCom CLIENT REQUEST:" + ex);
		}

		catch (IOException ioe) {
			System.out.println("From ServletCom CLIENT REQUEST:" + ioe);
		}
		try {
			inStream = new DataInputStream(conn.getInputStream());
			String str;
			while ((str = inStream.readLine()) != null) {
				System.out.println(str);
				label.setText(" "+nombre);
				labelrespuesta.setText(str);

			}
		//actualizarRegistro(codigo_producto,creado_prestashop2);
		//actualizarRegistroImagen(codigo_producto);
			inStream.close();

		} catch (IOException ioex) {
			System.out.println("From (ServerResponse): " + ioex);

		}

	}

	public void getFileDatabase() throws IOException {
		JFileChooser fileChooser = new JFileChooser();
		fileChooser.setFileFilter(new FileNameExtensionFilter("database file","accdb"));
		fileChooser.setCurrentDirectory(new File(System.getProperty("user.home")));
		int result = fileChooser.showOpenDialog(this);
		if (result == JFileChooser.APPROVE_OPTION) {
			File selectedFile = fileChooser.getSelectedFile();
			rutaarchivo = selectedFile.getAbsolutePath();
			escribirRutaBase(rutaarchivo);
			baseDeDatos.setText(rutaarchivo);
			button.setEnabled(false);
			progressDbNueva trProgressDbNueva = new progressDbNueva();
			trProgressDbNueva.start();
		}
	}

	@Override
	public void actionPerformed(ActionEvent e) {
		Container f = this.getContentPane();
		if (e.getSource() == mi1) {
			try {
				getFileDatabase();
			} catch (IOException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
		}
		if (e.getSource() == mi2) {
			f.setBackground(new Color(0, 255, 0));
		}
		if (e.getSource() == mi3) {
			f.setBackground(new Color(0, 0, 255));
		}

	}

}
