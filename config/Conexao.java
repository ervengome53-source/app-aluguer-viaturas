import java.sql.*;

class Conexao {
	public static Connection getconnection () {
	
		try {
		//Class.forName("com.mysql.cj.jdbc.Driver");
		Connection conexao = DriverMananger.getConnection("jdbc:mysql://localhost:3306/FormularioBD", "root", "");
					return conexao;
			}catch (SQLException A){
			System.out.println("Erro na BD" + A.getMessage());
					return null;
					}
					
					
	}
 }